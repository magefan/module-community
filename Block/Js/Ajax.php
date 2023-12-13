<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Js;

use Magento\Framework\View\Element\Template;

class Ajax extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Magefan_Community::js/ajax.phtml';

    static protected $rendered;

    /**
     * @return string
     */
    public function toHtml() {
        if (self::$rendered) {
            return '';
        }
        self::$rendered = 1;
        return parent::toHtml();
    }
}