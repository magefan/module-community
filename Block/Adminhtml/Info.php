<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\Community\Api\SecureHtmlRendererInterface;
use Magefan\Community\Api\GetModuleInfoInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Route\ConfigInterface as RouteConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Admin Magefan info block for extension grid/index pages
 */
class Info extends \Magento\Backend\Block\Template
{
    /**
     * @var RouteConfigInterface
     */
    private $routeConfig;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var GetModuleVersionInterface
     */
    protected $getModuleVersion;

    /**
     * @var SecureHtmlRendererInterface
     */
    protected $mfSecureRenderer;

    /**
     * @var GetModuleInfoInterface
     */
    protected $getModuleInfo;

    /**
     * Map of full action names to Magefan module names for extensions
     * that enhance native Magento admin pages (no own route).
     * Format: ['full_action_name' => 'Magefan_ModuleName']
     *
     * @var array
     */
    private $fullActionModuleMap;

    /**
     * @param Context $context
     * @param RouteConfigInterface $routeConfig
     * @param ModuleListInterface $moduleList
     * @param array $data
     * @param GetModuleVersionInterface|null $getModuleVersion
     * @param SecureHtmlRendererInterface|null $mfSecureRenderer
     * @param GetModuleInfoInterface|null $getModuleInfo
     * @param array $fullActionModuleMap
     */
    public function __construct(
        Context $context,
        RouteConfigInterface $routeConfig,
        ModuleListInterface $moduleList,
        array $data = [],
        ?GetModuleVersionInterface $getModuleVersion = null,
        ?SecureHtmlRendererInterface $mfSecureRenderer = null,
        ?GetModuleInfoInterface $getModuleInfo = null,
        array $fullActionModuleMap = []
    ) {
        parent::__construct($context, $data);
        $this->routeConfig = $routeConfig;
        $this->moduleList = $moduleList;
        $this->fullActionModuleMap = $fullActionModuleMap;
        $this->getModuleVersion = $getModuleVersion ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            GetModuleVersionInterface::class
        );
        $this->mfSecureRenderer = $mfSecureRenderer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SecureHtmlRendererInterface::class);
        $this->getModuleInfo = $getModuleInfo ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(GetModuleInfoInterface::class);
    }

    /**
     * Set default template
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Magefan_Community::info.phtml');
    }

    /**
     * Resolve the Magefan module name for the current admin page.
     *
     * Priority:
     * 1. module_name data argument (set explicitly via layout XML)
     * 2. Route-based detection (extensions with their own admin route)
     * 3. Full-action map (extensions that enhance native Magento pages)
     *
     * @return string  e.g. "Magefan_Blog", or empty string if not a Magefan page
     */
    public function getModuleName(): string
    {
        $moduleName = $this->getData('module_name');
        if ($moduleName) {
            return (string)$moduleName;
        }

        $request = $this->getRequest();
        $frontName = $request->getRouteName();

        if ($frontName) {
            $modules = $this->routeConfig->getModulesByFrontName($frontName, 'adminhtml');
            foreach ($modules as $module) {
                if (strpos($module, 'Magefan_') === 0) {
                    return $module;
                }
            }
        }

        $fullAction = $request->getFullActionName();
        if ($fullAction && isset($this->fullActionModuleMap[$fullAction])) {
            return $this->fullActionModuleMap[$fullAction];
        }

        return '';
    }

    /**
     * Get the module info DataObject from the remote API
     *
     * @return DataObject
     */
    public function getModuleInfo(): DataObject
    {
        return $this->getModuleInfo->execute($this->getModuleName());
    }

    /**
     * Human-readable module title, e.g. "Blog Extension", "Better Order Grid Extension"
     *
     * @return string
     */
    public function getModuleTitle(): string
    {
        $productName = $this->getModuleInfo()->getProductName();
        if ($productName) {
            return trim(str_replace(['Magento 2 ', 'Magento '], '', (string)$productName));
        }
        $parts = explode('_', $this->getModuleName());
        return isset($parts[1]) ? ucwords(str_replace('_', ' ', $parts[1])) . ' Extension' : '';
    }

    /**
     * Get the product URL for this module
     *
     * @return string
     */
    public function getModuleUrl(): string
    {
        return (string)($this->getModuleInfo()->getProductUrl() ?: 'https://magefan.com/');
    }

    /**
     * Currently installed version (uses Plus/Extra variant if active)
     *
     * @return string
     */
    public function getCurrentVersion(): string
    {
        $moduleName = $this->getModuleName();
        foreach (['Extra', 'Plus'] as $plan) {
            if ($v = $this->getModuleVersion->execute($moduleName . $plan)) {
                return $v;
            }
        }
        return $this->getModuleVersion->execute($moduleName);
    }

    /**
     * Latest available version from remote
     *
     * @return string
     */
    public function getLatestVersion(): string
    {
        return (string)$this->getModuleInfo()->getVersion();
    }

    /**
     * Whether the extension is enabled, using its own Model/Config::isEnabled().
     * Auto-resolves Magefan\{ShortName}\Model\Config from the module name.
     * Returns true if the Config class does not exist (no enable toggle).
     *
     * @return bool
     */
    public function isExtensionEnabled(): bool
    {
        $parts = explode('_', $this->getModuleName());
        if (!isset($parts[1])) {
            return true;
        }
        $configClass = 'Magefan\\' . $parts[1] . '\\Model\\Config';
        if (!class_exists($configClass)) {
            return true;
        }
        try {
            $config = \Magento\Framework\App\ObjectManager::getInstance()->get($configClass);
            return $config->isEnabled();
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Whether a newer version is available
     *
     * @return bool
     */
    public function needToUpdate(): bool
    {
        $latest = $this->getLatestVersion();
        $current = $this->getCurrentVersion();
        return $latest && $current && version_compare($latest, $current) > 0;
    }

    /**
     * Whether a plan upgrade is available (e.g. free → Plus/Extra)
     *
     * @return bool
     */
    public function canUpgradeToMaxPlan(): bool
    {
        $maxPlan = $this->getModuleInfo()->getMaxPlan();
        if (!$maxPlan) {
            return false;
        }
        return !$this->getModuleVersion->execute($this->getModuleName() . ucfirst($maxPlan));
    }

    /**
     * Expose secure HTML renderer to the template
     *
     * @return SecureHtmlRendererInterface
     */
    public function getMfSecureRenderer(): SecureHtmlRendererInterface
    {
        return $this->mfSecureRenderer;
    }

    /**
     * Only render on Magefan extension pages where the module is installed.
     * For own-route extensions: restricted to index/grid actions.
     * For native-page extensions: shown on any action listed in the map.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        $moduleName = $this->getModuleName();
        if (!$moduleName) {
            return '';
        }

        $fullAction = $this->getRequest()->getFullActionName();
        $isMappedAction = isset($this->fullActionModuleMap[$fullAction]);

        if (!$isMappedAction && $this->getRequest()->getActionName() !== 'index') {
            return '';
        }

        if (!$this->getModuleVersion->execute($moduleName)) {
            return '';
        }

        if (!$this->isExtensionEnabled()) {
            return '';
        }

        return parent::_toHtml();
    }
}
