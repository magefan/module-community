<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Remindlater;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    /**
     * Save remind later entry for current admin user
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $event      = (string)$this->getRequest()->getParam('event');
        $moduleName = (string)$this->getRequest()->getParam('module');
        $userId     = (int)$this->_auth->getUser()->getId();

        if (!$event || !$moduleName || !$userId) {
            return $result->setData(['success' => false, 'message' => 'Missing parameters.']);
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $table = $this->resourceConnection->getTableName('mf_message_remind_later');

            $connection->insertOnDuplicate(
                $table,
                ['admin_user_id' => $userId, 'module_name' => $moduleName, 'event' => $event],
                ['created_at']
            );

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
