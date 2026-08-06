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
use Magefan\Community\Model\Config;
use Magento\Config\Model\Config\Structure;
use Magefan\Community\Model\ResourceModel\RemindLater as RemindLaterResource;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\Module\Manager as ModuleManager;

/**
 * Admin Magefan info block for extension grid/index pages
 */
class Info extends \Magento\Backend\Block\Template
{
    /**
     * @var Structure
     */
    private $configStructure;

    /**
     * @var RouteConfigInterface
     */
    private $routeConfig;

    /**
     * @var RemindLaterResource
     */
    private $remindLaterResource;

    /**
     * @var AuthSession
     */
    private $authSession;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

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
     * @var string|null
     */
    private $moduleNameCache;

    /**
     * @var DataObject|null
     */
    private $moduleInfoCache;

    /**
     * @var string|null
     */
    private $configSectionCache;

    /**
     * @param Context $context
     * @param Config $config
     * @param RouteConfigInterface $routeConfig
     * @param Structure $configStructure
     * @param RemindLaterResource $remindLaterResource
     * @param AuthSession $authSession
     * @param ModuleManager $moduleManager
     * @param array $data
     * @param GetModuleVersionInterface|null $getModuleVersion
     * @param SecureHtmlRendererInterface|null $mfSecureRenderer
     * @param GetModuleInfoInterface|null $getModuleInfo
     * @param array $fullActionModuleMap
     */
    public function __construct(
        Context $context,
        Config $config,
        RouteConfigInterface $routeConfig,
        Structure $configStructure,
        RemindLaterResource $remindLaterResource,
        AuthSession $authSession,
        ModuleManager $moduleManager,
        array $data = [],
        ?GetModuleVersionInterface $getModuleVersion = null,
        ?SecureHtmlRendererInterface $mfSecureRenderer = null,
        ?GetModuleInfoInterface $getModuleInfo = null,
        array $fullActionModuleMap = []
    ) {
        parent::__construct($context, $data);
        $this->configStructure = $configStructure;
        $this->remindLaterResource = $remindLaterResource;
        $this->authSession = $authSession;
        $this->moduleManager = $moduleManager;
        $this->config = $config;
        $this->routeConfig = $routeConfig;
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
        if ($this->moduleNameCache !== null) {
            return $this->moduleNameCache;
        }

        $moduleName = $this->getData('module_name');
        if ($moduleName) {
            return $this->moduleNameCache = (string)$moduleName;
        }

        $request = $this->getRequest();
        $frontName = $request->getRouteName();

        if ($frontName) {
            $modules = $this->routeConfig->getModulesByFrontName($frontName, 'adminhtml');
            foreach ($modules as $module) {
                if (strpos($module, 'Magefan_') === 0) {
                    return $this->moduleNameCache = $module;
                }
            }
        }

        $fullAction = $request->getFullActionName();
        if ($fullAction && isset($this->fullActionModuleMap[$fullAction])) {
            $value = $this->fullActionModuleMap[$fullAction];
            return $this->moduleNameCache = is_array($value) ? (string)reset($value) : (string)$value;
        }

        return $this->moduleNameCache = '';
    }

    /**
     * Whether the module was resolved via the full-action map.
     *
     * @return bool
     */
    public function isFullActionModule(): bool
    {
        $fullAction = $this->getRequest()->getFullActionName();
        if (!$fullAction || !isset($this->fullActionModuleMap[$fullAction])) {
            return false;
        }
        $value = $this->fullActionModuleMap[$fullAction];
        $modules = is_array($value) ? $value : [$value];
        return in_array($this->getModuleName(), $modules, true);
    }

    /**
     * Whether the current admin user clicked "Remind Later" for the given event.
     *
     * @param string $event
     * @return bool
     */
    public function isRemindedLater(string $event): bool
    {
        if ($event === 'enabled' && !$this->isFullActionModule()) {
            return false;
        }

        try {
            $userId = (int)$this->authSession->getUser()->getId();
        } catch (\Exception $e) {
            return true;
        }
        if (!$userId) {
            return true;
        }

        try {
            $moduleName = $this->getModuleName();
            $row = $this->remindLaterResource->getRow($userId, $moduleName, $event);

            if (!$row) {
                if ($event !== 'enabled') {
                    $this->remindLaterResource->insert($userId, $moduleName, $event);
                    return true;
                }
                return false;
            }

            $remindAt = strtotime($row['created_at']) + 30 * 24 * 3600;
            return time() < $remindAt;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get the module info DataObject from the remote API
     *
     * @return DataObject
     */
    public function getModuleInfo(): DataObject
    {
        if ($this->moduleInfoCache !== null) {
            return $this->moduleInfoCache;
        }
        return $this->moduleInfoCache = $this->getModuleInfo->execute($this->getModuleName());
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
     * Human-readable module title, e.g. "Blog Extension", "Better Order Grid Extension"
     *
     * @return string
     */
    public function getModuleChangeLogUrl(): string
    {
        return $this->getModuleInfo()->getChangeLogUrl();
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
            return (bool)$config->isEnabled();
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
     * Config section key for the current module, or empty string if not found.
     *
     * @return string
     */
    public function getConfigSection(): string
    {
        if ($this->configSectionCache !== null) {
            return $this->configSectionCache;
        }

        $moduleName = $this->getModuleName();
        if (!$moduleName) {
            return $this->configSectionCache = '';
        }

        try {
            $tabs = $this->configStructure->getTabs();
        } catch (\Exception $e) {
            return $this->configSectionCache = '';
        }

        $sections = [];
        foreach ($tabs as $tab) {
            if (in_array($tab->getId(), ['magefan', 'mf_extensions_list'])) {
                // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                $sections = array_merge($sections, $tab->getData()['children']);
            }
        }

        foreach ($sections as $key => $section) {
            if (empty($section['resource']) || strpos($section['resource'], 'Magefan_') !== 0) {
                continue;
            }
            $parts = explode(':', $section['resource']);
            if ($parts[0] === $moduleName) {
                return $this->configSectionCache = $key;
            }
        }

        return $this->configSectionCache = '';
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
        $fullAction = $this->getRequest()->getFullActionName();

        if ($fullAction && isset($this->fullActionModuleMap[$fullAction]) && !$this->getData('module_name')) {
            $value = $this->fullActionModuleMap[$fullAction];
            $modules = is_array($value) ? $value : [$value];
            if (count($modules) > 1) {
                $html = '';
                foreach ($modules as $module) {
                    if (!$this->moduleManager->isEnabled($module)) {
                        continue;
                    }
                    $this->moduleNameCache = null;
                    $this->moduleInfoCache = null;
                    $this->configSectionCache = null;
                    $this->setData('module_name', $module);
                    $html .= parent::_toHtml();
                }
                $this->unsetData('module_name');
                return $html;
            }
        }

        $moduleName = $this->getModuleName();
        if (!$moduleName || !$this->moduleManager->isEnabled($moduleName)) {
            return '';
        }

        $isMappedAction = $fullAction && isset($this->fullActionModuleMap[$fullAction]);
        if (!$isMappedAction && $this->getRequest()->getActionName() !== 'index') {
            return '';
        }
        return parent::_toHtml();
    }
}
