<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magefan\Community\Api\GetModuleInfoInterface;
use Magefan\Community\Model\SectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;

/** Promo page of Plus/Extra feature, content comes from layout arguments. */
class Promo extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Magefan_Community::promo.phtml';

    /**
     * @var SectionFactory
     */
    private $sectionFactory;

    /**
     * @var GetModuleInfoInterface
     */
    private $getModuleInfo;

    /**
     * @param Context $context
     * @param SectionFactory $sectionFactory
     * @param GetModuleInfoInterface $getModuleInfo
     * @param array $data
     */
    public function __construct(
        Context $context,
        SectionFactory $sectionFactory,
        GetModuleInfoInterface $getModuleInfo,
        array $data = []
    ) {
        $this->sectionFactory = $sectionFactory;
        $this->getModuleInfo = $getModuleInfo;
        parent::__construct($context, $data);
    }

    /**
     * Get the plan upgrade URL for the configured product key, or the pricing page
     *
     * @return string
     */
    public function getUpgradePlanUrl(): string
    {
        $utmParams = $this->getUtmParams();
        $section = (string)$this->getData('section');
        $productKey = $section
            ? (string)$this->sectionFactory->create(['name' => $section])->getKey()
            : '';

        if ($productKey) {
            return 'https://magefan.com/mfplanupgrade/upgrade/index?product_key=' . urlencode($productKey)
                . '&' . $utmParams;
        }

        $moduleName = (string)$this->getData('module_name');
        $productUrl = $moduleName
            ? (string)$this->getModuleInfo->execute($moduleName)->getProductUrl()
            : '';

        return $productUrl
            ? rtrim($productUrl, '/') . '/pricing?' . $utmParams
            : 'https://magefan.com/';
    }

    /**
     * Get UTM params for the upgrade links
     *
     * @return string
     */
    private function getUtmParams(): string
    {
        return http_build_query([
            'utm_source' => 'admin',
            'utm_medium' => 'feature-promo-page',
            'utm_campaign' => (string)$this->getData('utm_campaign'),
        ]);
    }
}
