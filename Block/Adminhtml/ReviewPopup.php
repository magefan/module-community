<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\App\Route\Config as RouteConfig;
use Magefan\Community\Model\Config;
class ReviewPopup extends \Magento\Backend\Block\Template
{
    private $reviewUrl = null;
    private $moduleInfo = null;

    /**
     * @var \Magefan\Community\Model\GetModuleInfo
     */
    private $getModuleInfo;

    /**
     * @var RouteConfig
     */
    private $routeConfig;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magefan\Community\Model\GetModuleInfo $getModuleInfo
     * @param RouteConfig $routeConfig
     * @param Config $config
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\Community\Model\GetModuleInfo $getModuleInfo,
        RouteConfig $routeConfig,
        Config $config,
        \Magento\Backend\Model\Auth\Session $authSession,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null)
    {
        $this->getModuleInfo = $getModuleInfo;
        $this->routeConfig = $routeConfig;
        $this->config = $config;
        $this->_authSession = $authSession;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Get module name
     *
     * @return string|null
     */
    public function getModuleName() {
        $frontModule = $this->routeConfig->getModulesByFrontName($this->getRequest()->getModuleName());

        if (!empty($frontModule[0]) && strpos($frontModule[0], 'Magefan_') !== false) {
            return $frontModule[0];
        }
        return null;
    }

    /**
     * Get module review url
     *
     * @return mixed|null
     */
    public function getModuleReviewUrl()
    {
        if ($this->reviewUrl === null) {
            $info = $this->getModuleInfo();

            if (!empty($info['review_url'])) {
                $this->reviewUrl = $info['review_url'];
            }
        }

        return $this->reviewUrl;
    }

    /**
     * Get the product name
     *
     * @return string
     */
    public function getProductName(): string
    {
        $info = $this->getModuleInfo();;
        if (!empty($info['product_name'])) {
           return str_replace('Magento 2', 'Magefan' , $info['product_name']);
        }
        return '';
    }

    /**
     * Get module info
     *
     * @return array|\Magento\Framework\DataObject|mixed
     */
    private function getModuleInfo() {
        if ($this->reviewUrl === null) {
            $this->moduleInfo = $this->getModuleInfo->execute($this->getModuleName());
        }
        return $this->moduleInfo;
    }


    /**
     * Check if we can display block
     *
     * @return bool
     */
    private function canDisplay() : bool
    {
        $display = true;
        $moduleName = $this->getModuleName();
        if ($moduleName && strpos($moduleName, '_') !== false) {
            $moduleName = explode('_', $moduleName)[1];
            $extra = $this->_authSession->getUser()->getExtra();
            if (!empty($extra)) {
                $extra = json_decode($extra, true);
                $rev = $extra['mf_review'][$moduleName] ?? null;
                if ($rev) {
                    if ($rev['leave_review'] === false) {
                        if (!empty($rev['updated_at'])) {
                            try {
                                $given = new \DateTime($rev['updated_at']);
                                $threeDaysAgo = new \DateTime('-3 days');
                                if ($given > $threeDaysAgo) {
                                    $display = false;
                                }

                            } catch (\Exception $e) {
                            }
                        }
                    } else {
                        $display = false;
                    }
                }

            }
        }
        return $this->config->receiveReview() && $this->getModuleReviewUrl() && $this->getProductName() && $display;
    }

    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->canDisplay()) {
            return '';
        }
        return parent::_toHtml();
    }
}