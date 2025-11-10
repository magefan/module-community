<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class ReviewPopup extends \Magento\Backend\Block\Template
{
    /**
     * @var \Magefan\Community\Model\GetModuleInfo
     */
    private $getModuleInfo;

    private $reviewUrl = null;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\Community\Model\GetModuleInfo $getModuleInfo,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null)
    {
        $this->getModuleInfo = $getModuleInfo;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    public function getModuleName() {
        return ucfirst($this->_request->getModuleName());
    }

    public function getModuleReviewUrl()
    {
        if ($this->reviewUrl === null) {
            $info = $this->getModuleInfo->execute($this->getModuleName());

            if (!empty($info['review_url'])) {
                $this->reviewUrl = $info['review_url'];
            }
        }

        return $this->reviewUrl;
    }

    private function canDisplay() {
        return $this->getModuleReviewUrl(); // && перевірити чи є
    }

    /**
     * Prepare html output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->canDisplay()) {
            return '';
        }
        return parent::_toHtml();
    }
}