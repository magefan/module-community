<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\Type\Config;

class SetLinvFlag
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * Set flag
     *
     * @param string $module
     * @param string $value
     * @param string $errorMessage
     * @return void
     */
    public function execute($module, $value, $errorMessage = '')
    {
        $path = $module . '/g'.'en'.'er'.'al'.'/';
        $scopeDefault = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->configWriter->save($path . 'l'.'in'.'v', $value, $scopeDefault, 0);
        $this->configWriter->save(
            $path . 'l'.'in'.'v'.'_'.'error_me'.'ss'.'ag'.'e',
            $errorMessage,
            $scopeDefault,
            0
        );
        $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
    }
}
