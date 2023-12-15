<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block;

use Magento\Framework\View\Element\Template;

class JsScript extends Template
{
    /**
     * @var array
     */
    private static $rendered = [];

    /**
     * @var string
     */
    private $jsMethod;

    /**
     * @param string $method
     * @return JsScript
     */
    public function setMethod(string $method): JsScript
    {
        $this->jsMethod = $method;
        return $this;
    }

    public function getTemplate()
    {
        if (!$this->_template) {
            $this->_template = 'Magefan_Community::js/' . $this->jsMethod . '.phtml';
        }
        return parent::getTemplate();
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if (isset(self::$rendered[$this->jsMethod])) {
            return '';
        }
        self::$rendered[$this->jsMethod] = 1;
        return parent::toHtml();
    }
}
