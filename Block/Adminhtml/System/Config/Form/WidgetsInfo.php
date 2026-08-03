<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\Community\Api\SecureHtmlRendererInterface;
use Magefan\Community\Api\GetModuleInfoInterface;
use Magento\Backend\Block\Template\Context;
use Magefan\Community\Model\Section;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Admin Magefan configurations information block
 */
class WidgetsInfo extends \Magento\Config\Block\System\Config\Form\Field
{
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
     * @var GetModuleInfoInterface|ModuleInfoInterface|mixed
     */
    protected $getModuleInfo;

    /**
     * @var \Magento\Widget\Model\Widget|null
     */
    protected $widgetModel;

    /**
     * @param ModuleListInterface $moduleList
     * @param Context $context
     * @param array $data
     * @param GetModuleVersionInterface|null $getModuleVersion
     * @param SecureHtmlRendererInterface|null $mfSecureRenderer
     * @param ModuleInfoInterface|null $getModuleInfo
     * @param \Magento\Widget\Model\Widget|null $widgetModel
     */
    public function __construct(
        ModuleListInterface $moduleList,
        Context $context,
        array $data = [],
        ?GetModuleVersionInterface $getModuleVersion = null,
        ?SecureHtmlRendererInterface $mfSecureRenderer = null,
        ?GetModuleInfoInterface $getModuleInfo = null,
        ?\Magento\Widget\Model\Widget $widgetModel = null
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->widgetModel = $widgetModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Widget\Model\Widget::class);
        $this->getModuleVersion = $getModuleVersion ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Community\Api\GetModuleVersionInterface::class
        );
        $this->mfSecureRenderer = $mfSecureRenderer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SecureHtmlRendererInterface::class);
        $this->getModuleInfo = $getModuleInfo ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(GetModuleInfoInterface::class);
    }

    /**
     * Return info block html
     *
     * @param  AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        return $this->renderWidgetsInfo($element);
    }

    /**
     * Render widgets card HTML, auto-detecting widgets from widget.xml by module namespace.
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function renderWidgetsInfo(AbstractElement $element): string
    {
        $fieldConfig = $element->getFieldConfig();
        $path        = explode('/', (string)($fieldConfig['path'] ?? ''));
        $section     = ObjectManager::getInstance()->create(Section::class, ['name' => $path[0]]);
        $moduleName  = 'Magefan_' . str_replace(['Plus', 'Extra'], '', $section->getModuleName());
        $baseNamespace = str_replace('_', '\\', $moduleName);
        $namespaces = [
            $baseNamespace . '\\',
            $baseNamespace . 'Plus\\',
            $baseNamespace . 'Extra\\',
        ];
        $moduleWidgets = [];
        foreach ($this->widgetModel->getWidgets() as $code => $widget) {
            $class = $widget['@']['type'] ?? '';
            $matches = false;
            foreach ($namespaces as $namespace) {
                if (strpos($class, $namespace) === 0) {
                    $matches = true;
                    break;
                }
            }
            if ($matches) {
                $moduleWidgets[] = [
                    'name'        => (string)($widget['name'] ?? ''),
                    'description' => (string)($widget['description'] ?? ''),
                    'code'        => $code,
                ];
            }
        }

        if (empty($moduleWidgets)) {
            return '';
        }

        $chevron = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="#eb5202"'
            . ' stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M9 6l6 6-6 6"/></svg>';

        $html = '<div style="background:#fff;border-radius:10px;'
            . 'box-shadow:0 1px 3px rgba(0,0,0,0.06),0 8px 24px rgba(0,0,0,0.06);margin-bottom:30px;">'
            . '<div style="padding:24px 28px 22px;">'
            . '<div style="font-size:16px;font-weight:700;color:#1a1a1a;margin-bottom:5px;">'
            .   'Widgets included in this extension'
            . '</div>'
            . '<p style="margin:0 0 18px;font-size:13.5px;line-height:1.55;color:#767676;">'
            .   'Add these widgets to any CMS page or block via'
            .   ' <strong style="color:#4a4a4a;">Content > Elements > Widgets</strong>.'
            .   ' Click a widget to open it in Magento and preview its options.'
            . '</p>'
            . '<div style="display:flex;flex-direction:column;gap:8px;">';

        foreach ($moduleWidgets as $widget) {
            $name        = $this->escapeHtml($widget['name']);
            $description = $this->escapeHtml($widget['description']);
            $widgetUrl   = $this->escapeHtml(
                $this->getUrl('adminhtml/widget_instance/new', ['code' => $widget['code']])
            );

            $html .= '<a href="' . $widgetUrl . '" '
                . ' style="display:flex;align-items:center;justify-content:space-between;gap:16px;'
                . 'padding:14px 16px;border:1px solid #ececec;border-radius:8px;background:#fbfbfb;'
                . 'text-decoration:none;transition:border-color 0.15s,background 0.15s;">'
                . '<div>'
                .   '<div style="font-size:14px;font-weight:600;color:#1a1a1a;">' . $name . '</div>'
                .   ($description
                        ? '<div style="font-size:12.5px;color:#8f8f8f;margin-top:3px;">' . $description . '</div>'
                        : '')
                . '</div>'
                . '<span style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;'
                .   'color:#eb5202;white-space:nowrap;flex-shrink:0;">Open in Widgets ' . $chevron . '</span>'
                . '</a>';
        }

        $html .= '</div></div></div>';

        return $html;
    }

}
