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
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Form\FormKey;
use Magefan\Community\Model\Config;

class ReviewPopup extends \Magento\Backend\Block\Template
{
    /**
     * @var string|null
     */
    private $reviewUrl = null;

    /**
     * @var array|null
     */
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
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var FormKey
     */
    private $mfFormKey;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magefan\Community\Model\GetModuleInfo $getModuleInfo
     * @param RouteConfig $routeConfig
     * @param Config $config
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param ResourceConnection $resourceConnection
     * @param FormKey $formKey
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
        ResourceConnection $resourceConnection,
        FormKey $formKey,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->getModuleInfo = $getModuleInfo;
        $this->routeConfig = $routeConfig;
        $this->config = $config;
        $this->authSession = $authSession;
        $this->resourceConnection = $resourceConnection;
        $this->mfFormKey = $formKey;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Return the full Magefan module name for the current admin route.
     *
     * Prefers an explicit `module_name` argument passed via layout XML.
     * Falls back to resolving by the current route front name.
     *
     * @return string|null
     */
    public function getModuleName()
    {
        $explicit = $this->getData('module_name');
        if ($explicit) {
            return $explicit;
        }

        $routeName = $this->getRequest()->getRouteName();
        $modules = $this->routeConfig->getModulesByFrontName($routeName);
        foreach ($modules as $module) {
            if (strpos($module, 'Magefan_') === 0) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Return the short module name without the vendor prefix.
     *
     * @return string
     */
    public function getModuleShortName(): string
    {
        $moduleName = $this->getModuleName();
        if ($moduleName && strpos($moduleName, '_') !== false) {
            return explode('_', $moduleName)[1];
        }
        return (string)$moduleName;
    }

    /**
     * Return the external review submission URL for the current module.
     *
     * @return string|null
     */
    public function getModuleReviewUrl()
    {
        if ($this->reviewUrl === null) {
            $info = $this->getModuleInfo();

            if (!empty($info['popup_review_url'])) {
                $this->reviewUrl = $info['popup_review_url'];
            }
        }

        return $this->reviewUrl;
    }

    /**
     * Return the product display name for the current module.
     *
     * @return string
     */
    public function getProductName(): string
    {
        $info = $this->getModuleInfo();
        if (!empty($info['product_name'])) {
           return str_replace('Magento 2', 'Magefan' , $info['product_name']);
        }
        return '';
    }

    /**
     * Return the current admin form key for CSRF protection.
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->mfFormKey->getFormKey();
    }

    /**
     * Return cached module info from the remote source.
     *
     * @return array|\Magento\Framework\DataObject|mixed
     */
    private function getModuleInfo()
    {
        if ($this->moduleInfo === null) {
            $this->moduleInfo = $this->getModuleInfo->execute($this->getModuleName());
        }
        return $this->moduleInfo;
    }

    /**
     * Check whether the review popup can be displayed for the current user and module.
     *
     * @return bool
     */
    private function canDisplay(): bool
    {
        if (!$this->config->receiveReview()) {
            return false;
        }

        $moduleName = $this->getModuleShortName();
        if (!$moduleName) {
            return false;
        }

        $user = $this->authSession->getUser();
        if (!$user) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('mf_review'))
            ->where('user_id = ?', (int)$user->getId())
            ->where('module_name = ?', $moduleName)
            ->limit(1);

        $review = $connection->fetchRow($select);
        if ($review) {
            if ((int)$review['is_reviewed'] !== 0) {
                return false;
            }
            $updatedAt = $review['updated_at'] ?? null;
            if ($updatedAt) {
                try {
                    if (new \DateTime($updatedAt) > new \DateTime('-3 days')) {
                        return false;
                    }
                } catch (\Exception $e) {
                    // ignore invalid date
                }
            }
        }

        return $this->getModuleReviewUrl()
            && $this->getProductName();
    }

    /**
     * Render block HTML or return empty string when popup should not be shown.
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
