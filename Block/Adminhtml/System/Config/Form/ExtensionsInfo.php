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
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->getModuleVersion = $getModuleVersion;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $products = $this->getJsonObject();
        if (!$products) {
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

            foreach ($products as $productKey => $product) {

                $moduleName = 'Magefan_' . $productKey;
                $module = $this->moduleList->getOne($moduleName);

                if ((!$module && $listKey != 'new_extensions') || ($module && $listKey == 'new_extensions')) {
                    continue;
                }
                if ($listKey == 'up_to_date' && version_compare($this->getModuleVersion->execute($moduleName), $product->version) < 0) {
                    continue;
                }
                if ($listKey == 'need_update' && version_compare($this->getModuleVersion->execute($moduleName), $product->version) >= 0) {
                    continue;
                }

                if ($listKey == 'need_update') {
                    $version = $this->getModuleVersion->execute($moduleName) . ' -> ' . $product->version;
                } elseif ($listKey == 'new_extensions') {
                    $version = $product->version;
                } else {
                    $version = $this->getModuleVersion->execute($moduleName);
                }


                $html .= '<tr>';
                $html .= '<td><a target="_blank" href="' . $this->escapeHtml($product->product_url) . '">' . $this->escapeHtml($product->product_name) . '</a></td>';
                $html .= '<td>' . $this->escapeHtml($version) . '</td>';
                $html .= '<td><a target="_blank" href="' . $this->escapeHtml($product->change_log_url) . '">' . $this->escapeHtml(__('Change Log')) . '</a></td>';
                $html .= '<td><a target="_blank" href="' . $this->escapeHtml($product->documentation_url) . '">'. $this->escapeHtml(__('User Guide')). '</a></td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table></br>';
        }
        return $html;
    }

    /**
     * @return mixed
     */
    public function getJsonObject()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'https://magefan.com/media/product-versions-extended.json');
        $result = curl_exec($ch);
        curl_close($ch);

        $obj = json_decode($result);
        return $obj;
    }
}
