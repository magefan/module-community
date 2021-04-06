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

class ExtensionsInfo extends Field
{
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * ExtensionsInfo constructor.
     * @param Context $context
     * @param ModuleListInterface $moduleList
     * @param array $data
     */
    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
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
            'new_versions' => __('New Version Available'),
            'up_to_date' => __('Up-to-Date Magefan Extensions'),
            'available' => __('Available Magefan Extensions'),
        ];

        $html = '';
        foreach ($lists as $key => $lable) {
            $html .= '<strong>' . $this->escapeHtml($lable) . '</strong>';
            $html .= '<table class="data-grid">';
            $html .= '<thead>';
            $html .= '<tr>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('Product Name')) . '</th>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('Version')) . '</th>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('Change Log')) . '</th>';
            $html .= '<th class="data-grid-th">' . $this->escapeHtml(__('User Guide')) . '</th>';
            $html .= '</tr>';
            $html .= '</thead>';
            $html .= '<tbody class="magefan-section">';

            foreach ($products as $key => $product) {
                $module = $this->moduleList->getOne('Magefan_' . $key);
                $continue = false;

                switch ($key) {
                    case 'new_versions':
                        $version = $module['setup_version'];
                        if (!$module) {
                            $continue = true;
                        }
                        break;
                    case 'up_to_date':
                        $version = $module['setup_version'];
                        if (!$module) {
                            $continue = true;
                        }
                        if ($product->version != $module['setup_version']) {
                            $continue = true;
                        }
                        break;
                    case 'available':
                        $version = $product->version;
                        if ($module) {
                            $continue = true;
                        }
                        break;
                    default:
                        $version  = '';
                }

                if ($continue) {
                    continue;
                }

                $html .= '<tr>';
                $html .= '<td><a href="' . $this->escapeHtml($product->product_url) . '">' . $this->escapeHtml($product->product_name) . '</a></td>';
                $html .= '<td>' . $this->escapeHtml($product->version) . '</td>';
                $html .= '<td><a href="' . $this->escapeHtml($product->change_log_url) . '">' . $this->escapeHtml(__('Change Log')) . '</a></td>';
                $html .= '<td><a href="' . $this->escapeHtml($product->documentation_url) . '">'. $this->escapeHtml(__('User Guide')). '</a></td>';
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
