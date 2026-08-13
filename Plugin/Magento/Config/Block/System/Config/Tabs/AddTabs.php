<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Config\Block\System\Config\Tabs;

use Magento\Config\Block\System\Config\Tabs;
use Psr\Log\LoggerInterface;

class AddTabs
{
    public const TAB_CLASS = 'magefan-tab';

    /**
     * @var \DOMDocumentFactory
     */
    private $domFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \DOMDocumentFactory $domFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        \DOMDocumentFactory $domFactory,
        LoggerInterface $logger
    ) {
        $this->domFactory = $domFactory;
        $this->logger = $logger;
    }

    /**
     * @param Tabs $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(Tabs $subject, string $result): string
    {
        try {
            $domDocument = $this->domFactory->create();
            $this->loadHtml($domDocument, $result);

            if ($tabElelent = $this->getMagefanTabElement($domDocument)) {
                $tabsHtml = $subject
                    ->getLayout()
                    ->createBlock(
                        \Magefan\Community\Block\Adminhtml\System\Config\Tabs::class,
                        'mf_dynamic_config_tabs'
                    )->toHtml();

                // Splice $tabsHtml in as a raw string via a placeholder marker,
                // instead of re-parsing it into a second DOMDocument and importing
                // nodes across documents: that path mangles non-ASCII characters
                // into numeric HTML entities on saveHTML().
                $marker = uniqid('magefan_tabs_', true);
                $tabElelent->appendChild($domDocument->createComment($marker));

                $result = $this->saveHtml($domDocument);
                $result = str_replace('<!--' . $marker . '-->', $tabsHtml, $result);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
    }

    /**
     * Load (possibly non well-formed) HTML markup into a DOMDocument without
     * choking on unescaped ampersands or undefined HTML entities (e.g. &nbsp;),
     * which DOMDocument::loadXML() treats as fatal errors.
     *
     * @param \DOMDocument $domDocument
     * @param string $html
     * @return void
     */
    private function loadHtml(\DOMDocument $domDocument, string $html): void
    {
        $previousUseErrors = libxml_use_internal_errors(true);

        $domDocument->loadHTML(
            '<?xml encoding="utf-8" ?>' . $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previousUseErrors);
    }

    /**
     * Serialize node-by-node (not DOMDocument::saveHTML() with no argument),
     * which is required to avoid DOMDocument entity-encoding non-ASCII
     * characters into numeric HTML entities.
     *
     * @param \DOMDocument $domDocument
     * @return string
     */
    private function saveHtml(\DOMDocument $domDocument): string
    {
        $html = '';
        foreach ($domDocument->childNodes as $node) {
            if ($node->nodeType === XML_PI_NODE) {
                continue;
            }
            $html .= $domDocument->saveHTML($node);
        }

        return $html;
    }

    /**
     * @param \DOMDocument $domDocument
     * @return \DOMElement|null
     */
    private function getMagefanTabElement(\DOMDocument $domDocument): ?\DOMElement
    {
        foreach ($domDocument->getElementsByTagName('div') as $element) {
            if (stripos($element->getAttribute('class'), self::TAB_CLASS) !== false) {
                foreach ($element->getElementsByTagName('ul') as $ulElement) {
                    $element->removeChild($ulElement);
                }

                return $element;
            }
        }

        return null;
    }
}
