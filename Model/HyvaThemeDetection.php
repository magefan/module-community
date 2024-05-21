<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\HyvaThemeDetectionInterface;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
class HyvaThemeDetection implements HyvaThemeDetectionInterface
{

    /**
     * @var ModuleManager
     */
    protected $moduleManager;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ThemeProviderInterface
     */
    protected $themeProvider;

    /**
     * @param ModuleManager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param ThemeProviderInterface $themeProvider
     */
    public function __construct(
        ModuleManager $moduleManager,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        ThemeProviderInterface $themeProvider

    ) {
        $this->moduleManager = $moduleManager;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->themeProvider = $themeProvider;
    }

    /**
     * @param $storeId
     * @return bool
     * @throws NoSuchEntityException
     */
    public function execute($storeId = null): bool
    {
        $hyvaThemeEnabled = $this->moduleManager->isEnabled('Hyva_Theme');

        if ($hyvaThemeEnabled) {

            if (null === $storeId) {
                $storeId = $this->storeManager->getStore()->getId();
            }

            if (!$storeId){
                $stores = $this->storeManager->getStores();
                foreach ($stores as $store) {
                    if ($this->isHyvaThemeInUse($store->getId())) {
                        return true;
                    }
                }
            } else {
                return $this->isHyvaThemeInUse($storeId);
            }
        }
        return false;
    }

    /**
     * @param $storeId
     * @return bool
     */
    private function isHyvaThemeInUse($storeId)
    {
        $themeId = $this->scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($themeId) {
            $theme = $this->themeProvider->getThemeById($themeId);
            while ($theme) {
                $themePath = $theme->getThemePath();
                if (false !== stripos($themePath, 'h' . 'y' . 'v' . 'a')) {
                    return true;
                }

                $theme = $theme->getParentTheme();
            }
        }
        return false;
    }
}
