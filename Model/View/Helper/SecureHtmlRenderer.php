<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\View\Helper;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\ObjectManagerInterface;
use Magefan\Community\Api\SecureHtmlRendererInterface;

class SecureHtmlRenderer implements SecureHtmlRendererInterface
{
    /**
     * @var ProductMetadataInterface
     */
    private $productMetadata;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ObjectManagerInterface $objectManager
    ) {
       $this->productMetadata = $productMetadata;
       $this->objectManager = $objectManager;
    }

    /**
     * @param string $tagName
     * @param array $attributes
     * @param string|null $content
     * @param bool $textContent
     * @return string
     */
    public function renderTag(
        string $tagName,
        array $attributes,
        ?string $content = null,
        bool $textContent = true
    )
    {
        $version = $this->productMetadata->getVersion();
        if (version_compare($version, '2.4.0',">")) {
            return $this->objectManager->get(\Magento\Framework\View\Helper\SecureHtmlRenderer::class)->renderTag($tagName, $attributes, $content, $textContent);
        } else {
            $attrs = [];
            if ($attributes) {
                foreach ($attributes as $key => $value) {
                    $attrs[] = $key . '="' . $value . '"';
                }
            }
            return '<' . $tagName . ' ' . implode(' ', $attrs) . '>' . $content . '</' . $tagName . '>';
        }

    }
}
