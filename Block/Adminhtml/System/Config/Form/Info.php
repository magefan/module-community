<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magefan\Community\Api\GetModuleVersionInterface;

/**
 * Admin Magefan configurations information block
 */
class Info extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var GetModuleVersionInterface
     */
    protected $getModuleVersion;

    /**
     * Info constructor.
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param GetModuleVersionInterface|null $getModuleVersion
     */
    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        GetModuleVersionInterface $getModuleVersion = null
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->getModuleVersion = $getModuleVersion ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Community\Api\GetModuleVersionInterface::class
        );
    }

    /**
     * Return info block html
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $useUrl = \Magefan\Community\Model\UrlChecker::showUrl($this->getUrl());
        $version = $this->getModuleVersion->execute($this->getModuleName());
        $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">
            ' . $this->escapeHtml($this->getModuleTitle()) . ' v' . $this->escapeHtml($version) . ' was developed by ';
        if ($useUrl) {
            $html .= '<a href="' . $this->escapeHtml($this->getModuleUrl()) . '" target="_blank">Magefan</a>';
        } else {
            $html .= '<strong>Magefan</strong>';
        }
        $html .= '.</div>';

        return $html;
    }

    /**
     * Return extension url
     * @return string
     */
    protected function getModuleUrl()
    {
        return 'https://magefan.com/';
    }

    /**
     * Return extension title
     * @return string
     */
    protected function getModuleTitle()
    {
        return ucwords(str_replace('_', ' ', $this->getModuleName())) . ' Extension';
    }
}
