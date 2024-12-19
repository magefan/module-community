<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Backend\Block\Template\Context;
use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\Community\Api\GetModuleInfoInterface;

class ExtensionsInfo extends Field
{
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var GetModuleVersionInterface
     */
    private $getModuleVersion;

    /**
     * @var GetModuleInfoInterface
     */
    private $getModuleInfo;

    /**
     * ExtensionsInfo constructor.
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param GetModuleVersionInterface $getModuleVersion
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        GetModuleVersionInterface $getModuleVersion,
        GetModuleInfoInterface $getModuleInfo,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->getModuleVersion = $getModuleVersion;
        $this->getModuleInfo = $getModuleInfo;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $modulesInfo = $this->getModuleInfo->execute();
        if (!$modulesInfo) {
            return '';
        }

        $lists = [
            'need_update' => __('Extensions to Update'),
            'up_to_date' => __('Up-to-Date Extensions'),
            'new_extensions' => __('Available NEW Extensions'),
        ];

        $html = '';
        foreach ($lists as $listKey => $lable) {
            $html .= '<h3>' . $this->escapeHtml($lable) . '</h3>';
            $html .= '<table class="data-grid">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('Extension')) . '</th>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('Version')) . '</th>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('Change Log')) . '</th>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('User Guide')) . '</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody class="magefan-section">';

            foreach ($modulesInfo as $moduleKey => $moduleInfo) {

                $moduleName = 'Magefan_' . $moduleKey;
                $module = $this->moduleList->getOne($moduleName);

                if ((!$module && $listKey != 'new_extensions') || ($module && $listKey == 'new_extensions')) {
                    continue;
                }
                if ($listKey == 'up_to_date' && version_compare($this->getModuleVersion->execute($moduleName), $moduleInfo->getVersion()) < 0) {
                    continue;
                }
                if ($listKey == 'need_update' && version_compare($this->getModuleVersion->execute($moduleName),  $moduleInfo->getVersion()) >= 0) {
                    continue;
                }

                if ($listKey == 'need_update') {
                    $version = $this->getModuleVersion->execute($moduleName) . ' -> ' .  $moduleInfo->getVersion();
                } elseif ($listKey == 'new_extensions') {
                    $version =  $moduleInfo->getVersion();
                } else {
                    $version = $this->getModuleVersion->execute($moduleName);
                }


                $html .= '<tr>';
                $html .= '<td><a target="_blank" href="' . $this->escapeHtml($moduleInfo->getProductUrl()) . '">' . $this->escapeHtml($moduleInfo->getProductName()) . '</a></td>';
                $html .= '<td>' . $this->escapeHtml($version) . '</td>';
                $html .= '<td><a target="_blank" href="' . $this->escapeHtml($moduleInfo->getChangeLogUrl()) . '">' . $this->escapeHtml(__('Change Log')) . '</a></td>';
                $html .= '<td><a target="_blank" href="' . $this->escapeHtml($moduleInfo->getDocumentationUrl()) . '">'. $this->escapeHtml(__('User Guide')). '</a></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table></br>';
        }
        return $html;
    }

}
