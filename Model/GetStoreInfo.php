<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetStoreInfoInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class GetStoreInfo implements GetStoreInfoInterface
{
    private const XML_PATH_STORE_NAME = 'general/store_information/name';

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get store info
     *
     * @param int|null $storeId
     * @return DataObject
     */
    public function execute($storeId = null)
    {
        $store = $this->storeManager->getStore($storeId);

        $name = (string)$this->scopeConfig->getValue(
            self::XML_PATH_STORE_NAME,
            ScopeInterface::SCOPE_STORE,
            $store->getId()
        );

        if (!$name) {
            $name = (string)$store->getFrontendName();
        }

        return new DataObject([
            'name' => $name,
            'url' => $store->getBaseUrl(UrlInterface::URL_TYPE_WEB),
        ]);
    }
}
