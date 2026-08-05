<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Config\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Switches header/footer email template config values between Magefan-branded and Magento default templates
 */
class UseMagefanEmailTemplates extends Value
{
    private const XML_PATH_HEADER_TEMPLATE = 'mfextension/email/header_template';
    private const XML_PATH_FOOTER_TEMPLATE = 'mfextension/email/footer_template';

    private const MAGEFAN_HEADER_TEMPLATE = 'mfextension_email_header_template';
    private const MAGEFAN_FOOTER_TEMPLATE = 'mfextension_email_footer_template';

    private const MAGENTO_HEADER_TEMPLATE = 'design_email_header_template';
    private const MAGENTO_FOOTER_TEMPLATE = 'design_email_footer_template';

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param WriterInterface $configWriter
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        WriterInterface $configWriter,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->configWriter = $configWriter;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @inheritDoc
     */
    public function afterSave()
    {
        $result = parent::afterSave();

        $useMagefanTemplates = (bool)$this->getValue();

        $this->configWriter->save(
            self::XML_PATH_HEADER_TEMPLATE,
            $useMagefanTemplates ? self::MAGEFAN_HEADER_TEMPLATE : self::MAGENTO_HEADER_TEMPLATE,
            $this->getScope(),
            $this->getScopeId()
        );
        $this->configWriter->save(
            self::XML_PATH_FOOTER_TEMPLATE,
            $useMagefanTemplates ? self::MAGEFAN_FOOTER_TEMPLATE : self::MAGENTO_FOOTER_TEMPLATE,
            $this->getScope(),
            $this->getScopeId()
        );

        $this->invalidateConfigCache();

        return $result;
    }

    /**
     * Reset header/footer template values back to their own defaults when "Use Default" is selected
     *
     * @inheritDoc
     */
    public function afterDelete()
    {
        $result = parent::afterDelete();

        $this->configWriter->delete(
            self::XML_PATH_HEADER_TEMPLATE,
            $this->getScope(),
            $this->getScopeId()
        );
        $this->configWriter->delete(
            self::XML_PATH_FOOTER_TEMPLATE,
            $this->getScope(),
            $this->getScopeId()
        );

        $this->invalidateConfigCache();

        return $result;
    }

    /**
     * Invalidate config cache
     *
     * @return void
     */
    private function invalidateConfigCache(): void
    {
        $this->cacheTypeList->invalidate(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }
}