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
     * @param GetModuleVersionInterface $getModuleVersion
     * @param SecureHtmlRendererInterface $mfSecureRenderer
     * @param GetModuleInfoInterface $getModuleInfo
     * @param \Magento\Widget\Model\Widget $widgetModel
     * @param array $data
     */
    public function __construct(
        ModuleListInterface $moduleList,
        Context $context,
        GetModuleVersionInterface $getModuleVersion,
        SecureHtmlRendererInterface $mfSecureRenderer,
        GetModuleInfoInterface $getModuleInfo,
        \Magento\Widget\Model\Widget $widgetModel,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->widgetModel = $widgetModel;
        $this->getModuleVersion = $getModuleVersion;
        $this->mfSecureRenderer = $mfSecureRenderer;
        $this->getModuleInfo = $getModuleInfo;
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
        $moduleName = $this->getModuleName();
        if ($moduleName == 'Magefan_Community') {
            $fieldConfig = $element->getFieldConfig();
            $path = explode('/', (string)($fieldConfig['path'] ?? ''));
            $section = ObjectManager::getInstance()->create(Section::class, ['name' => $path[0]]);
            $moduleName = (string)$section->getModuleName();
        }

        if (!$moduleName) {
            return '';
        }
        $baseModule = str_replace(['Magefan_','Plus', 'Extra'], '', $moduleName);
        $namespacePrefix = 'Magefan\\' . $baseModule;
        $moduleWidgets = [];
        foreach ($this->widgetModel->getWidgets() as $code => $widget) {
            $class = $widget['@']['type'] ?? '';
            if (strpos($class, $namespacePrefix) !== false) {
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
            .   $this->escapeHtml(__('Widgets included in this extension:'))
            . '</div>'
            . '<p style="margin:0 0 18px;font-size:13.5px;line-height:1.55;color:#767676;">'
            .   $this->escapeHtml(__('You can add these widgets to any content like CMS pages, blocks, products description or via'))
            .   ' <strong style="color:#4a4a4a;">' . $this->escapeHtml(__('Content > Elements > Widgets')) . '</strong>.'

            . '</p>'
            . '<div style="display:flex;flex-direction:column;gap:8px;">';

        foreach ($moduleWidgets as $widget) {
            $name        = $this->escapeHtml($widget['name']);
            $description = $widget['description'];
            $widgetUrl   = $this->escapeHtml(
                $this->getUrl('adminhtml/widget_instance/new', ['mf_wcode' => $widget['code']])
            );

            $html .= '<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;'
                . 'padding:14px 16px;border:1px solid #ececec;border-radius:8px;background:#fbfbfb;">'
                . '<div>'
                .   '<div style="font-size:14px;font-weight:600;color:#1a1a1a;">' . $name . '</div>'
                .   ($description
                        ? '<div style="font-size:12.5px;color:#8f8f8f;margin-top:3px;">' . $description . '</div>'
                        : '')
                . '</div>'
                . '<a href="' . $widgetUrl . '" target="_blank"'
                .   ' style="display:flex;align-items:center;gap:6px;font-size:13px;font-weight:600;'
                .   'color:#eb5202;white-space:nowrap;flex-shrink:0;text-decoration:none;">'
                .   'Open in Widgets ' . $chevron
                . '</a>'
                . '</div>';
        }

        $html .= '</div></div></div>';

        return $html;
    }

}
