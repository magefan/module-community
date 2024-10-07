<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magefan\Community\Block\Adminhtml;

class MfPartner extends \Magento\Backend\Block\Template
{
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
       \Magefan\Community\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    public function toHtml()
    {
        if (!$this->config->extensionVendorsEnabled()) {
            return '';
        }

        return parent::toHtml();
    }
}
