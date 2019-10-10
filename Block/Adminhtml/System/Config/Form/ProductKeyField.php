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

class ProductKeyField extends Field
{
    /**
     * @var mixed
     */
    protected $module;

    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->module = ObjectManager::getInstance()->get(Section::class);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->module->getModule()) {
            if (!$element->getComment()) {
                $url = strrev('/stcudorp/remotsuc/elbadaolnwod/moc.nafegam//:sptth');
                $element->setComment('You can find product key in your <a href="' . $url . '" target="_blank">Magefan account</a>.');
            }
            if (!$element->getLabel()) {
                $element->setLabel('Product Key');
            }
            return parent::render($element);
        } else {
            return '';
        }
    }
}