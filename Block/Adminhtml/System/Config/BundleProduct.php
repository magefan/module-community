<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml\System\Config;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class BundleProduct extends \Magento\Backend\Block\Template
{
    private $moduleManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\Community\Model\ModuleManager $moduleManager,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null)
    {
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getModules()
    {
        return $this->moduleManager->getAllSections();
    }
}