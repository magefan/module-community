<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 *
 * Glory to Ukraine! Glory to the heroes!
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Module\Manager as ModuleManager;
use Magefan\Community\Api\HyvaThemeDetectionInterface;

class HyvaThemeChecker extends Template
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var HyvaThemeDetectionInterface
     */
    private $hyvaThemeDetection;

    /**
     * @param Context $context
     * @param ModuleManager $moduleManager
     * @param HyvaThemeDetectionInterface $hyvaThemeDetection
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleManager $moduleManager,
        HyvaThemeDetectionInterface $hyvaThemeDetection,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleManager = $moduleManager;
        $this->hyvaThemeDetection =$hyvaThemeDetection;
    }

        /**
     * @return array
     */
    public function getWitchModuleIsInstalled(): array
    {
        $moduleGroups = [
            'Blog' => [
                'Magefan_BlogExtra' => 'magefan/hyva-theme-blog-extra',
                'Magefan_BlogPlus' => 'magefan/hyva-theme-blog-plus',
                'Magefan_Blog' => 'magefan/hyva-theme-blog'
            ],
            'AutoRelatedProduct' => [
                'Magefan_AutoRelatedProductPlus' => 'magefan/hyva-theme-auto-related-product-plus',
                'Magefan_AutoRelatedProduct' => 'magefan/hyva-theme-auto-related-product'
            ],
            'AutoLanguageSwitcher' => [
                'Magefan_AutoLanguageSwitcher' => 'magefan/hyva-theme-auto-language-switcher'
            ]
        ];

        $hyvaModules = [];
        foreach ($moduleGroups as $groupKey => $modules) {
            foreach ($modules as $module => $packageName) {
                if ($this->moduleManager->isEnabled($module)) {
                    $hyvaModule = 'Hyva_' . str_replace('_', '', $module);
                    if (!$this->moduleManager->isEnabled($hyvaModule)) {
                        $hyvaModules[$hyvaModule] = $packageName;
                        break;
                    }
                }
            }
        }
        return $hyvaModules;
    }

    /**
     * Produce and return block's html output
     *
     * This method should not be overridden. You can override _toHtml() method in descendants if needed.
     *
     * @return string
     */
    public function toHtml()
    {
        if (!$this->hyvaThemeDetection->execute()) {
            return '';
        }

        return parent::toHtml();
    }
}
