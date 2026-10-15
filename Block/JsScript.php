<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block;

use Magento\Framework\Exception\NoSuchEntityException;
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
     * Set method
     *
     * @param string $method
     * @return JsScript
     */
    public function setMethod(string $method): JsScript
    {
        $this->jsMethod = $method;
        return $this;
    }

    /**
     * Get js template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->_template) {
            $this->_template = 'Magefan_Community::js/' . $this->jsMethod . '.phtml';
        }
        return parent::getTemplate();
    }

    /**
     * Get current website ID
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getWebsiteId(): int
    {
        return (int)$this->_storeManager->getStore()->getWebsiteId();
    }

    /**
     * Add custom html
     *
     * @return string
     */
    public function toHtml()
    {
        if ($this->getScriptAttributes()) {
            $hash = '_' . sha1(json_encode($this->getScriptAttributes()));
        } else {
            $hash = '';
        }

        if (isset(self::$rendered[$this->jsMethod . $hash])) {
            return '';
        }
        self::$rendered[$this->jsMethod] = 1;
        self::$rendered[$this->jsMethod . $hash] = 1;
        return parent::toHtml();
    }
}
