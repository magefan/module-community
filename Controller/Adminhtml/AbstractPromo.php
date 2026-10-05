<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/** Base controller for promo pages of Plus and Extra features. */
abstract class AbstractPromo extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Page title of the promoted feature
     *
     * @return string
     */
    abstract protected function getPageTitle(): string;

    /**
     * Admin menu item to highlight, empty for none
     *
     * @return string
     */
    protected function getActiveMenu(): string
    {
        return '';
    }

    /**
     * Show the promo page
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        if ($this->getActiveMenu()) {
            $resultPage->setActiveMenu($this->getActiveMenu());
        }
        $resultPage->getConfig()->getTitle()->prepend(__($this->getPageTitle()));
        return $resultPage;
    }
}