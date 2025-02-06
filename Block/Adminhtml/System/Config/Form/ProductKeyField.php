<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magefan\Community\Model\Section;
use Magento\Framework\App\ObjectManager;

/**
 * Class Product Key Field
 */
class ProductKeyField extends Field
{
    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $objectManager = ObjectManager::getInstance();
        $fieldConfig = $element->getFieldConfig();
        $path = explode('/', $fieldConfig['path']);
        $path = $path[0];

        $section = $objectManager->create(Section::class, ['name' => $path]);
        if ($section->getModule()) {
            if (!$element->getComment()) {
                $url = 'htt' . 'ps' . ':' . '/'. '/'. 'ma' . 'g' . 'ef' . 'an' . '.' . 'co'
                    . 'm/' . 'down' . 'loa' . 'dab' . 'le/' . 'cus' . 'tomer' . '/' . 'pr' . 'od' . 'ucts' . '/';
                $element->setComment('You can find product key in your <a href="' . $url . '" target="_blank">Magefan account</a>.');
            }
            return parent::render($element);
        } else {
            $config = ObjectManager::getInstance()->get(\Magefan\Community\Model\Config::class);
            $bp = $section->getName() . '/' . 'g' . 'e' . 'n' . 'e' . 'r' . 'a' . 'l' . '/' ;
            if (!$config->getConfig( $bp . Section::ACTIVE) && !$section->getType()) {
                    $url = 'ht' . 'tps'. ':' . '/'. '/'. 'ma' . 'g' . 'ef' . 'an' . '.' . 'c' . 'o' . 'm' . '/' . 'mp' . 'k/a' . 'cti' . 'vat' . 'e/e' . 'xte' . 'nsi' . 'on/' . 'ret' . 'urn_' . 'ur' . 'l/' .
                        base64_encode($this->getUrl('m' . 'f' . 'co' . 'mm' . 'uni' . 'ty/' . 'act' . 'iva' . 'te/ext' . 'ens' . 'ion', ['section' => $section->getName()]))
                        . '/mo' . 'dul' . 'e/' . $section->getModuleName() . '/se' . 'cti' . 'on/' . $section->getName();
                    return '
                        <tr id="row_mfblog_general_' . Section::ACTIVE . '">
                            <td class="label"></td>
                            <td class="value">
                                <button id="activate-extension" type="button" class="action-default scalable primary ui-button ui-corner-all ui-widget" onclick="window.open(\'' . $url . '\');">
                                    ' . __('Activate Extension') . '
                                </button>
                            </td>
                            <td class=""></td>
                        </tr>' ;
            }
        }
        return '';
    }
}
