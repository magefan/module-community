<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\BreezeThemeDetectionInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Design\Theme\ThemeProviderInterface;
class BreezeThemeDetection implements BreezeThemeDetectionInterface
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ThemeProviderInterface
     */
    private $themeProvider;

    /**
     * @var array
     */
    private $result;


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
        $key = 'store_' . $storeId;
        if (isset($this->result[$key])) {
            return $this->result[$key];
        }

        $breezeThemeEnabled = $this->moduleManager->isEnabled('Swissup_Breeze');

        if ($breezeThemeEnabled) {

            if (null === $storeId) {
                $storeId = $this->storeManager->getStore()->getId();
            }

            if (!$storeId){
                $stores = $this->storeManager->getStores();
                foreach ($stores as $store) {
                    if ($this->isBreezeThemeInUse($store->getId())) {
                        $this->result[$key] = true;
                        return $this->result[$key];
                    }
                }
            } else {
                $this->result[$key] = $this->isBreezeThemeInUse($storeId);
                return $this->result[$key];
            }
        }
        $this->result[$key] = false;
        return $this->result[$key];
    }

    /**
     * @param $storeId
     * @return bool
     */
    private function isBreezeThemeInUse($storeId)
    {
        $themeId = $this->scopeConfig->getValue(
            \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($themeId) {
            try {
                $theme = $this->themeProvider->getThemeById($themeId);
            } catch (\Exception $e) {
                $theme = false;
            }

            while ($theme) {
                $themePath = $theme->getThemePath();
                if (false !== stripos($themePath, 'b' . 'r' . 'e' . 'e' . 'z' . 'e')) {
                    return true;
                }

                $theme = $theme->getParentTheme();
            }
        }
        return false;
    }
}
