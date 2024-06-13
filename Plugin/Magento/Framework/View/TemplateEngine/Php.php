<?php
declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Framework\View\TemplateEngine;

use Magefan\Community\Model\View\Helper\SecureHtmlRenderer\Proxy as SecureHtmlRenderer;
use Magefan\Community\Model\HyvaThemeDetection\Proxy as HyvaThemeDetection;

class Php
{
    /**
     * @var SecureHtmlRenderer
     */
    private $mfSecureRenderer;

    /**
     * @var HyvaThemeDetection
     */
    private $mfHyvaThemeDetection;

    /**
     * @param SecureHtmlRenderer $mfSecureRenderer
     * @param HyvaThemeDetection $mfHyvaThemeDetection
     */
    public function __construct(
        SecureHtmlRenderer $mfSecureRenderer,
        HyvaThemeDetection $mfHyvaThemeDetection
    )
    {
        $this->mfSecureRenderer  = $mfSecureRenderer;
        $this->mfHyvaThemeDetection = $mfHyvaThemeDetection;
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
        $dictionary['mfHyvaThemeDetection'] = $this->mfHyvaThemeDetection;

        return [$block, $fileName, $dictionary];
    }
}

