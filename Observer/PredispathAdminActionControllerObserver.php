<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Observer;

use Magefan\Community\Model\AdminNotificationFeedFactory;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Community observer
 */
class PredispathAdminActionControllerObserver implements ObserverInterface
{
    /**
     * @var AdminNotificationFeedFactory
     */
    protected $feedFactory;

    /**
     * @var Session
     */
    protected $backendAuthSession;

    /**
     * @param AdminNotificationFeedFactory $feedFactory
     * @param Session $backendAuthSession
     */
    public function __construct(
        AdminNotificationFeedFactory $feedFactory,
        Session $backendAuthSession
    ) {
        $this->feedFactory = $feedFactory;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * Predispath admin action controller
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $feedModel = $this->feedFactory->create();
            /* @var $feedModel \Magefan\Community\Model\AdminNotificationFeed */
            $feedModel->checkUpdate();
        }
    }
}
