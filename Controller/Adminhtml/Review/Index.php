<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Review;

use Magefan\Community\Model\GetModuleInfo;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
use Psr\Log\LoggerInterface;

class Index extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    private const ACTION_CANCEL = 'cancel';

    private const ACTION_SUBMIT = 'submit';

    private const REQUIRED_FIELDS = [
        'ratings', 'nickname', 'firstname',
        'lastname', 'email', 'title',
        'detail'
    ];

    private const RATINGS_OPTION = [
        5 => [0 => 16],
        4 => [1 => 17],
        3 => [2 => 18],
        2 => [3 => 19],
        1 => [4 => 20]
    ];

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $_authSession;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var GetModuleInfo
     */
    private $getModuleInfo;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     * @param GetModuleInfo $getModuleInfo
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        JsonFactory $resultJsonFactory,
        Curl $curl,
        GetModuleInfo $getModuleInfo,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->_authSession = $authSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        $this->getModuleInfo = $getModuleInfo;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
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
        if ($reviewAction !== self::ACTION_SUBMIT) {
            return $result->setData(['success' => false, 'message' => __('Invalid action.')]);
        }

        $reviewUrl = $this->getReviewUrl($moduleName);
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

        $rating = (int)$reviewData['ratings'];
        if (!isset(self::RATINGS_OPTION[$rating])) {
            return $result->setData(['success' => false, 'message' => __('Please select a rating.')]);
        }
        $reviewData['ratings'] = self::RATINGS_OPTION[$rating];

        if (!filter_var($reviewData['email'], FILTER_VALIDATE_EMAIL)) {
            return $result->setData(['success' => false, 'message' => __('Please enter a valid email address.')]);
        }

        try {
            $this->curl->post($reviewUrl, $reviewData);
            $this->setReviewStatus($moduleName);
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
     * Resolve review submission URL on the server side, never trust the URL from request
     *
     * @param string $moduleName
     * @return string|null
     */
    private function getReviewUrl(string $moduleName): ?string
    {
        $info = $this->getModuleInfo->execute('Magefan_' . $moduleName);
        $url = $info ? (string)$info->getData('popup_review_url') : '';
        if (!$url) {
            return null;
        }

        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
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
            $this->setReviewStatus($moduleName, false);
            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $result->setData(['success' => false]);
        }
    }

    /**
     * Updates or inserts the review status for a given module and user.
     *
     * @param string $moduleName
     * @param bool $status
     *
     * @return void
     */
    private function setReviewStatus($moduleName, $status = true)
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('mf_review');
        $userId = (int)$this->_authSession->getUser()->getId();

        $select = $connection->select()
            ->from($tableName)
            ->where('user_id = ?', $userId)
            ->where('module_name = ?', $moduleName);
        $existing = $connection->fetchRow($select);

        $data = [
            'module_name' => $moduleName,
            'user_id' => $userId,
            'is_reviewed' => $status ? 1 : 0,
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $connection->update(
                $tableName,
                $data,
                ['id = ?' => (int)$existing['id']]
            );
        } else {
            $data['created_at'] = (new \DateTime())->format('Y-m-d H:i:s');
            $connection->insert($tableName, $data);
        }
    }
}
