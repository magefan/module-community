<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Email;

use Magento\Framework\View\Element\Template;

/**
 * Renders current year, used in the Magefan email footer.
 */
class CurrentYear extends Template
{
    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        return date('Y');
    }
}