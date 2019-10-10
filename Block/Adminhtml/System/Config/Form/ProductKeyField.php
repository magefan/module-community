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
     * @var \Magefan\Community\Model\Section
     */
    protected $section;

    /**
     * ProductKeyField constructor.
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->section = ObjectManager::getInstance()->get(Section::class);
    }

    /**
     * Retrieve HTML markup for given form element
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        if ($this->section->getModule()) {
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