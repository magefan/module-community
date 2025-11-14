<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Review;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;
class Index extends \Magento\Backend\App\Action
{
    private const REQUIRED_FIELDS = [
        'ratings', 'nickname', 'firstname',
        'lastname', 'email', 'title',
        'detail'
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
     * @param Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param JsonFactory $resultJsonFactory
     * @param Curl $curl
     */
    public function __construct(
        Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        JsonFactory $resultJsonFactory,
        Curl $curl
    )
    {
        $this->_authSession = $authSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        parent::__construct($context);
    }

    public function execute()
    {
        $reviewData = [];
        $result = $this->resultJsonFactory->create();
        $moduleName = $this->_request->getParam('module');
        if (!$moduleName) {
            return $result->setData(['success' => false, 'message' => __('Module name is not specified.')]);
        }
        $reviewAction = $this->_request->getParam('action');
        if (!$reviewAction) {
            return $result->setData(['success' => false, 'message' => __('Action is not specified.')]);
        }
        if ($reviewAction == 'cancel') {
          return $this->remindLater($moduleName);
        }

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!$this->_request->getParam($field)) {
                return $result->setData(['success' => false,'message' => __('Please fill all required fields.')]);
            }
            $reviewData[$field] = $this->_request->getParam($field);
        }
        try {
            $url = $reviewAction;
            $postData = $reviewData;

            $this->curl->post($url, $postData);

            $response = $this->curl->getBody();

            $this->setReviewStatus($moduleName);
            return $result->setData([
                'success' => true,
                'response' => $response
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function remindLater($moduleName)
    {
        $result = $this->resultJsonFactory->create();
        try {
            $this->setReviewStatus($moduleName, false);
            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false]);
        }
    }

    private function setReviewStatus($moduleName, $status = true)
    {
        $user = $this->_authSession->getUser();
        $extra = $user->getExtra();
        $extraArray = !empty($extra) ? json_decode($extra,true) : [];
        $extraArray['mf_review'][$moduleName] = [
            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'leave_review' => $status
        ];
        $user->setExtra(json_encode($extraArray));
        $user->save();
    }
}