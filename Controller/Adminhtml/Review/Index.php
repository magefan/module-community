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

    private const RATINGS_OPTION = [ 1 => [0=>16], 2 => [1=>17], 3 => [2=>18], 4 => [3=>19], 5 => [4=>20]];

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

            if ($field == 'ratings') {
                $reviewData[$field] = self::RATINGS_OPTION[$this->_request->getParam($field)];
            }
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
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resourceConnection = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resourceConnection->getConnection();
        $tableName = $resourceConnection->getTableName('mf_review');
        $userId = $this->_authSession->getUser()->getId();

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
                ['id = ?' => $existing['id']]
            );
        } else {
            $data['created_at'] = (new \DateTime())->format('Y-m-d H:i:s');
            $connection->insert($tableName, $data);
        }
    }
}