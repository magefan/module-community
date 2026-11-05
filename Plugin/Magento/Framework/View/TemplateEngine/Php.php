<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Framework\View\TemplateEngine;

use Magefan\Community\Model\BreezeThemeDetection\Proxy as BreezeThemeDetection;
use Magefan\Community\Model\View\Helper\SecureHtmlRenderer\Proxy as SecureHtmlRenderer;
use Magefan\Community\Model\HyvaThemeDetection\Proxy as HyvaThemeDetection;
use Magefan\Community\Model\Tailwind\Proxy as Tailwind;
use Magento\Framework\View\Element\BlockInterface;

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
     * @var BreezeThemeDetection
     */
    private $mfBreezeThemeDetection;

    /**
     * @var Tailwind
     */
    private $mfTailwind;

    // @codingStandardsIgnoreStart
    /**
     * @param SecureHtmlRenderer $mfSecureRenderer
     * @param HyvaThemeDetection $mfHyvaThemeDetection
     * @param BreezeThemeDetection $mfBreezeThemeDetection
     * @param Tailwind $mfTailwind
     */
    public function __construct(
        SecureHtmlRenderer $mfSecureRenderer,
        HyvaThemeDetection $mfHyvaThemeDetection,
        BreezeThemeDetection $mfBreezeThemeDetection,
        Tailwind $mfTailwind
    ) {
        $this->mfSecureRenderer  = $mfSecureRenderer;
        $this->mfHyvaThemeDetection = $mfHyvaThemeDetection;
        $this->mfBreezeThemeDetection = $mfBreezeThemeDetection;
        $this->mfTailwind = $mfTailwind;
    }
    // @codingStandardsIgnoreEnd

    /**
     * Add custom data
     *
     * @param \Magento\Framework\View\TemplateEngine\Php $subject
     * @param BlockInterface $block
     * @param string $fileName
     * @param array $dictionary
     * @return array
     */
    public function beforeRender(
        \Magento\Framework\View\TemplateEngine\Php $subject,
        BlockInterface $block,
        $fileName,
        array $dictionary = []
    ) {
        $dictionary['mfSecureRenderer'] = $this->mfSecureRenderer;
        $dictionary['mfHyvaThemeDetection'] = $this->mfHyvaThemeDetection;
        $dictionary['mfBreezeThemeDetection'] = $this->mfBreezeThemeDetection;
        $dictionary['mfTailwind'] = $this->mfTailwind;

        return [$block, $fileName, $dictionary];
    }
}
