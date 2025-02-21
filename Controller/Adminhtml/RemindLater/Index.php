<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\RemindLater;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class Index extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @param Context $context
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        Context $context,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\ResourceConnection $resource
    )
    {
        parent::__construct($context);
        $this->authSession = $authSession;
        $this->resource = $resource;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|(\Magento\Framework\Controller\Result\Json&\Magento\Framework\Controller\ResultInterface)|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        try {
            if (!$adminUser = $this->authSession->getUser()) {
                throw new NoSuchEntityException(__('Admin User is not found.'));
            }
            if (!$event = $this->_request->getParam('event')) {
                throw new NoSuchEntityException(__('Magento Event is not provided.'));
            }

            if (!$moduleName = $this->_request->getParam('module')) {
                throw new NoSuchEntityException(__('Magento Event is not provided.'));
            }

            $connection = $this->resource->getConnection();
            $tableName = $connection->getTableName('mf_message_remind_later');

            $data = [
                'user_id' => $adminUser->getId(),
                'module_name' => $moduleName,
                'event' => $event
            ];

            $select = $connection->select()
                ->from($tableName, ['id'])
                ->where('user_id = ?', $adminUser->getId())
                ->where('module_name = ?', $moduleName)
                ->where('event = ?', $event);
            $exists = $connection->fetchOne($select);

            if ($exists) {
                $connection->update(
                    $tableName,
                    ['created_at' => (new \DateTime())->format('Y-m-d H:i:s')],
                    [
                        'user_id = ?' => $adminUser->getId(),
                        'module_name = ?' => $moduleName,
                        'event = ?' => $event
                    ]
                );
            } else {
                $connection->insert($tableName, $data);
            }

            $result = ['success' => true];
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}