<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */


namespace Magefan\Community\Block;

class ExtensionVendors extends \Magento\Backend\Block\Template
{
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    protected $assetRepository;

    /**
     * ExtensionVendors constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\View\Asset\Repository $assetRepository
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->assetRepository = $assetRepository;
        parent::__construct($context);
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->_urlBuilder->getUrl($route, $params);
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->assetRepository->getUrl("Magefan_Community::images/logo-config-section.png");
    }
    /**
     * Return extension url
     * @return string
     */
    public function getModuleUrl()
    {
        return 'https://magefan.com/';
    }
}
