<?php

namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Backend\Block\Template\Context;

class UpToDate extends Field
{
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    public function __construct(
        Context $context,
        ModuleListInterface $moduleList,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
    }

    public function render(AbstractElement $element)
    {
        $products = $this->getJsonObject();

        $html = '<strong>' . $element->getLabel() . '</strong>';
        $html .= '<table class="data-grid">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th class="data-grid-th">Product Name</th>';
        $html .= '<th class="data-grid-th">Version</th>';
        $html .= '<th class="data-grid-th">Change Log</th>';
        $html .= '<th class="data-grid-th">Documentation</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody class="magefan-section">';

        foreach ($products as $key => $product) {
            $module = $this->moduleList->getOne('Magefan_' . $key);
            if (!$module) {
                continue;
            }

            if ($product->version != $module['setup_version']) {
                continue;
            }

            $html .= '<tr>';
            $html .= '<td><a href="' . $product->product_url . '">' . $product->product_name . '</a></td>';
            $html .= '<td>' . $module['setup_version'] . '</td>';
            $html .= '<td><a href="' . $product->change_log_url . '">Change Log</a></td>';
            $html .= '<td><a href="' . $product->documentation_url . '">Documentation</a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table></br>';
        return $html;
    }

    public function getJsonObject()
    {
        $ch = curl_init();
        // IMPORTANT: the below line is a security risk, read https://paragonie.com/blog/2017/10/certainty-automated-cacert-pem-management-for-php-software
        // in most cases, you should set it to true
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, 'http://magefan.loc/pub/media/product-versions-extended.json');
        $result = curl_exec($ch);
        curl_close($ch);

        $obj = json_decode($result);
        return $obj;
    }
}
