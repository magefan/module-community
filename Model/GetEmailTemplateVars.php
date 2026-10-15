<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetEmailTemplateVarsInterface;
use Magefan\Community\Api\GetModuleInfoInterface;
use Magefan\Community\Api\GetStoreInfoInterface;

class GetEmailTemplateVars implements GetEmailTemplateVarsInterface
{
    /**
     * @var GetModuleInfoInterface
     */
    private $getModuleInfo;

    /**
     * @var GetStoreInfoInterface
     */
    private $getStoreInfo;

    /**
     * @param GetModuleInfoInterface $getModuleInfo
     * @param GetStoreInfoInterface $getStoreInfo
     */
    public function __construct(
        GetModuleInfoInterface $getModuleInfo,
        GetStoreInfoInterface $getStoreInfo
    ) {
        $this->getModuleInfo = $getModuleInfo;
        $this->getStoreInfo = $getStoreInfo;
    }

    /**
     * Get common email template vars
     *
     * @param string $moduleName
     * @param int|null $storeId
     * @return array
     */
    public function execute(string $moduleName, $storeId = null): array
    {
        $moduleInfo = $this->getModuleInfo->execute($moduleName);
        $storeInfo = $this->getStoreInfo->execute($storeId);

        return [
            'product_url' => (string)($moduleInfo->getProductUrl() ?: 'https://magefan.com/'),
            'product_name' => (string)($moduleInfo->getProductName() ?: 'Magefan'),
            'store_url' => $storeInfo->getUrl(),
            'store_name' => $storeInfo->getName(),
        ];
    }
}
