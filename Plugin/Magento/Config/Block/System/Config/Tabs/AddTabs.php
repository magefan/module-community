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
            $domDocument->loadXML($result);

            if ($tabElelent = $this->getMagefanTabElement($domDocument)) {
                $fragment = $domDocument->createDocumentFragment();

                $tabsHtml = $subject
                    ->getLayout()
                    ->createBlock(
                        \Magefan\Community\Block\Adminhtml\System\Config\Tabs::class,
                        'mf_dynamic_config_tabs'
                    )->toHtml();

                $fragment->appendXML($tabsHtml);
                $tabElelent->appendChild($fragment);
                $result = $domDocument->saveHTML();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }

        return $result;
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
