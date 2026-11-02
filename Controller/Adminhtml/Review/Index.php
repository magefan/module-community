<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Review;

use Laminas\Uri\UriFactory;
use Magefan\Community\Model\Config;
use Magefan\Community\Model\ExtensionReview\GetReviewUrls;
use Magefan\Community\Model\ExtensionReview\ReviewStatus;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Index extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    private const ACTION_TOKEN = 'token';

    private const ACTION_CANCEL = 'cancel';

    private const ACTION_SUBMIT = 'submit';

    private const REQUIRED_FIELDS = [
        'rating', 'firstname', 'lastname',
        'email', 'title', 'detail', 'token'
    ];

    /**
     * Max time in seconds to establish connection
     */
    private const CONNECT_TIMEOUT = 3;

    /**
     * Max time in seconds for the whole request
     */
    private const TIMEOUT = 10;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $_authSession;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var GetReviewUrls
     */
    private $getReviewUrls;

    /**
     * @var ReviewStatus
     */
    private $reviewStatus;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param JsonFactory $resultJsonFactory
     * @param CurlFactory $curlFactory
     * @param GetReviewUrls $getReviewUrls
     * @param ReviewStatus $reviewStatus
     * @param StoreManagerInterface $storeManager
     * @param Json $json
     * @param LoggerInterface $logger
     * @param Config $config
     */
    public function __construct(
        Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        JsonFactory $resultJsonFactory,
        CurlFactory $curlFactory,
        GetReviewUrls $getReviewUrls,
        ReviewStatus $reviewStatus,
        StoreManagerInterface $storeManager,
        Json $json,
        LoggerInterface $logger,
        Config $config
    ) {
        $this->_authSession = $authSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curlFactory = $curlFactory;
        $this->getReviewUrls = $getReviewUrls;
        $this->reviewStatus = $reviewStatus;
        $this->storeManager = $storeManager;
        $this->json = $json;
        $this->logger = $logger;
        $this->config = $config;
        parent::__construct($context);
    }

    /**
     * Executes the main review processing logic based on request parameters.
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $reviewData = [];
        $result = $this->resultJsonFactory->create();
        // "Extension Review Requests" is disabled in the Magefan Notifications config
        if (!$this->config->isReviewRequestsEnabled()) {
            return $result->setData(['success' => false, 'message' => __('Review requests are disabled.')]);
        }
        $moduleName = $this->_request->getParam('module');
        if (!is_string($moduleName) || !preg_match('/^[A-Za-z0-9]+$/', $moduleName)) {
            return $result->setData(['success' => false, 'message' => __('Module name is not specified.')]);
        }
        $reviewAction = $this->_request->getParam('action');
        if (!$reviewAction) {
            return $result->setData(['success' => false, 'message' => __('Action is not specified.')]);
        }
        if ($reviewAction === self::ACTION_CANCEL) {
            return $this->remindLater($moduleName);
        }
        if ($reviewAction === self::ACTION_TOKEN) {
            return $this->getToken($moduleName);
        }
        if ($reviewAction !== self::ACTION_SUBMIT) {
            return $result->setData(['success' => false, 'message' => __('Invalid action.')]);
        }

        $reviewUrl = $this->getReviewUrls->execute($moduleName)['review'] ?? '';
        if (!$reviewUrl) {
            return $result->setData(['success' => false, 'message' => __('Review is not available for this module.')]);
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            $value = $this->_request->getParam($field);
            $value = is_scalar($value) ? trim((string)$value) : '';
            if ($value === '') {
                return $result->setData(['success' => false, 'message' => __('Please fill all required fields.')]);
            }
            $reviewData[$field] = $value;
        }

        $reviewData['rating'] = (int)$reviewData['rating'];
        if ($reviewData['rating'] < 1 || $reviewData['rating'] > 5) {
            return $result->setData(['success' => false, 'message' => __('Please select a rating.')]);
        }

        $reviewData['nickname'] = $reviewData['firstname'] . ' ' . $reviewData['lastname'];

        if (!filter_var($reviewData['email'], FILTER_VALIDATE_EMAIL)) {
            return $result->setData(['success' => false, 'message' => __('Please enter a valid email address.')]);
        }

        $country = $this->getDefaultCountry();
        if ($country) {
            $reviewData['country'] = $country;
        }

        $reviewData['domain'] = $this->getDomain();

        try {
            $response = $this->post($reviewUrl, $reviewData);

            if (!empty($response['reviewed'])) {
                // A review with this email already exists, so do not ask about this module again
                $this->reviewStatus->markReviewed($moduleName, $this->getAdminUserId());
                return $result->setData([
                    'success' => false,
                    'message' => __('You have already left a review for this extension. Thank you!')
                ]);
            }

            if (empty($response['success'])) {
                return $result->setData([
                    'success' => false,
                    'message' => !empty($response['message']) && is_string($response['message'])
                        ? $response['message']
                        : __('An error occurred while submitting your review. Please try again later.')
                ]);
            }

            $this->reviewStatus->markReviewed($moduleName, $this->getAdminUserId());
            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while submitting your review. Please try again later.')
            ]);
        }
    }

    /**
     * Default country of the store (General > Country Options), e.g. UA
     *
     * @return string
     */
    private function getDefaultCountry(): string
    {
        try {
            $country = (string)$this->storeManager->getDefaultStoreView()->getConfig('general/country/default');
        } catch (\Exception $e) {
            return '';
        }

        return preg_match('/^[A-Z]{2}$/', $country) ? $country : '';
    }

    /**
     * Request a one-time review token from Magefan, or learn that the admin user already left a review
     *
     * @param string $moduleName
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function getToken(string $moduleName)
    {
        $result = $this->resultJsonFactory->create();

        $tokenUrl = $this->getReviewUrls->execute($moduleName)['token'] ?? '';
        if (!$tokenUrl) {
            return $result->setData(['success' => false, 'message' => __('Review is not available for this module.')]);
        }

        try {
            $response = $this->post($tokenUrl, [
                'email' => (string)$this->_authSession->getUser()->getEmail(),
                'domain' => $this->getDomain()
            ]);

            if (!empty($response['reviewed'])) {
                $this->reviewStatus->markReviewed($moduleName, $this->getAdminUserId());
                return $result->setData(['success' => true, 'reviewed' => true]);
            }

            if (empty($response['success']) || empty($response['token']) || !is_string($response['token'])) {
                return $result->setData(['success' => false]);
            }

            return $result->setData(['success' => true, 'token' => $response['token']]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $result->setData(['success' => false]);
        }
    }

    /**
     * Send POST request to Magefan and return decoded JSON response
     *
     * @param string $url
     * @param array $data
     * @return array
     */
    private function post(string $url, array $data): array
    {
        $curl = $this->curlFactory->create();
        $curl->setTimeout(self::TIMEOUT);
        $curl->setOption(CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        $curl->post($url, $data);

        if (200 != $curl->getStatus()) {
            throw new \RuntimeException('Magefan review request failed with status ' . $curl->getStatus());
        }

        $response = $this->json->unserialize((string)$curl->getBody());

        return is_array($response) ? $response : [];
    }

    /**
     * Get the store domain the review comes from
     *
     * @return string
     */
    private function getDomain(): string
    {
        try {
            $baseUrl = $this->storeManager->getDefaultStoreView()->getBaseUrl();
            return (string)UriFactory::factory($baseUrl)->getHost();
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Handle a reminder to delay review prompt for a specific module.
     *
     * @param string $moduleName
     * @return \Magento\Framework\Controller\Result\Json
     */
    private function remindLater($moduleName)
    {
        $result = $this->resultJsonFactory->create();
        try {
            $this->reviewStatus->postpone($moduleName, $this->getAdminUserId());
            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $result->setData(['success' => false]);
        }
    }

    /**
     * Current admin user id
     *
     * @return int
     */
    private function getAdminUserId(): int
    {
        $user = $this->_authSession->getUser();
        return $user ? (int)$user->getId() : 0;
    }
}
