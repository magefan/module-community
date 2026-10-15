<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Promo;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;

/** Shows promo page of Plus/Extra feature registered by a module in di.xml. */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * Promo pages: [layout handle => ['resource' => ACL, 'menu' => menu id, 'title' => page title]]
     *
     * @var array
     */
    private $pages;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param array $pages
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        array $pages = []
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->pages = $pages;
    }

    /**
     * Show the promo page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $page = $this->getPage();
        if (!$page) {
            return $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_FORWARD)
                ->forward('noroute');
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->addHandle($this->getPageId());
        if (!empty($page['menu'])) {
            $resultPage->setActiveMenu($page['menu']);
        }
        $resultPage->getConfig()->getTitle()->prepend(__($page['title'] ?? ''));
        return $resultPage;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        $page = $this->getPage();
        return !$page || empty($page['resource']) || $this->_authorization->isAllowed($page['resource']);
    }

    /**
     * Get requested promo page id (its layout handle)
     *
     * @return string
     */
    private function getPageId(): string
    {
        return (string)$this->getRequest()->getParam('page');
    }

    /**
     * Get requested promo page config
     *
     * @return array|null
     */
    private function getPage(): ?array
    {
        $pageId = $this->getPageId();
        return isset($this->pages[$pageId]) && is_array($this->pages[$pageId]) ? $this->pages[$pageId] : null;
    }
}
