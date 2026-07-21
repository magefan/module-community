<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Remindlater;

use Magefan\Community\Model\ResourceModel\RemindLater as RemindLaterResource;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Index extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var RemindLaterResource
     */
    private $remindLaterResource;

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param RemindLaterResource $remindLaterResource
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RemindLaterResource $remindLaterResource
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->remindLaterResource = $remindLaterResource;
        parent::__construct($context);
    }

    /**
     * Skip secret key / form key validation — request comes from navigator.sendBeacon.
     *
     * @return bool
     */
    public function _processUrlKeys()
    {
        return true;
    }

    /**
     * Save remind later entry for current admin user
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $body = (string)$this->getRequest()->getContent();
        $json = $body ? (json_decode($body, true) ?: []) : [];

        $event      = (string)($this->getRequest()->getParam('event') ?: ($json['event'] ?? ''));
        $moduleName = (string)($this->getRequest()->getParam('module') ?: ($json['module'] ?? ''));
        $userId     = (int)$this->_auth->getUser()->getId();

        if (!$event || !$moduleName || !$userId) {
            return $result->setData(['success' => false, 'message' => 'Missing parameters.']);
        }

        try {
            $this->remindLaterResource->upsert($userId, $moduleName, $event);

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
