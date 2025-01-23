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
            if (!$config->getConfig($section->getName() . '/' . 'g' . 'e' . 'n' . 'e' . 'r' . 'a' . 'l' . '/' . 'm' . 'f' . 'a' . 'c' . 't' . 'i' . 'v' . 'e')
                && $config->getConfig($section->getName() . '/g' . 'e' . 'n' . 'e' . 'r' . 'a' . 'l' . '/' . 'm' . 'f' . 't' . 'y' . 'p' . 'e')) {
                $configStructure = $objectManager->get(\Magento\Config\Model\Config\Structure::class);
                $moduleSection = $configStructure->getElement($section->getName());
                if ($moduleSection && $moduleSection->getAttribute('resource')) {
                    $moduleName = explode(':', $moduleSection->getAttribute('resource'));
                    $moduleName = $moduleName[0];
                    $moduleInfo = $objectManager->get(\Magefan\Community\Api\GetModuleInfoInterface::class)->execute($moduleName);

                    $url = 'ht' . 'tp'. ':' . '/'. '/'. 'ma' . 'g' . 'ef' . 'an' . '.' . 'loc' . '/' . 'mp' . 'k/a' . 'cti' . 'vat' . 'e/e' . 'xte' . 'nsi' . 'on/' . 'ret' . 'urn_' . 'ur' . 'l/' .
                        base64_encode($this->getUrl('m' . 'f' . 'co' . 'mm' . 'uni' . 'ty/' . 'act' . 'iva' . 'te/ext' . 'ens' . 'ion', ['section' => $section->getName()]))
                        . '/mo' . 'dul' . 'e/' . $moduleInfo->getProductName() . '/se' . 'cti' . 'on/' . $section->getName();
                    return '<button id="activate-extension" type="button" class="action-default scalable primary ui-button ui-corner-all ui-widget" onclick="window.location.href=\'' . $url . '\';">Activate extension</button>';
                }
            }
        }
    }
}
