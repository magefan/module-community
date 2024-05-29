<?php
declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Framework\View\TemplateEngine;

use Magefan\Community\View\Helper\SecureHtmlRenderer\Proxy as SecureHtmlRenderer;

class Php
{
    /**
     * @var SecureHtmlRenderer
     */
    private $mfSecureRenderer;

    /**
     * @param SecureHtmlRenderer $mfSecureRenderer
     */
    public function __construct(
        SecureHtmlRenderer $mfSecureRenderer
    )
    {
        $this->mfSecureRenderer  = $mfSecureRenderer;
    }

    /**
     * @param \Magento\Framework\View\TemplateEngine\Php $subject
     * @param \Magento\Framework\View\Element\BlockInterface $block
     * @param $fileName
     * @param array $dictionary
     * @return array
     */
    public function beforeRender(
        \Magento\Framework\View\TemplateEngine\Php $subject,
        \Magento\Framework\View\Element\BlockInterface $block,
                                                          $fileName,
        array $dictionary = []
    ) {
        $dictionary['mfSecureRenderer'] = $this->mfSecureRenderer;

        return [$block, $fileName, $dictionary];
    }
}

