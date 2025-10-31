<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\App\CacheInterface;
use Magefan\Community\Api\GetModuleInfoInterface;
use Psr\Log\LoggerInterface;

class GetModuleInfo implements GetModuleInfoInterface
{
    public const CACHE_KEY = 'magefan_product_versions_extended_json';
    public const CACHE_LIFETIME = 43200;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var array
     */
    private $modulesInfo;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Curl $curl
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get data by module
     *
     * @param mixed $moduleName
     * @return array|DataObject|mixed
     */
    public function execute($moduleName = null)
    {
        $modulesInfo = $this->getModulesInfo();

        if (!$moduleName) {
            return $modulesInfo;
        }

        $moduleKey = explode('_', $moduleName)[1];

        if (!isset($modulesInfo[$moduleKey])) {
            $modulesInfo[$moduleKey] = new DataObject();
        }
        return $modulesInfo[$moduleKey];
    }

    /**
     * Get extension info
     *
     * @return array
     */
    public function getModulesInfo()
    {
        if (null === $this->modulesInfo) {
            $modulesInfo = $this->load();
            if (!$modulesInfo) {
                $modulesInfo = $this->loadFromCache();
            }

            foreach ($modulesInfo as $moduleKey => $moduleInfo) {
                $modulesInfo[$moduleKey] = new DataObject($moduleInfo);
            }

            $this->modulesInfo = $modulesInfo;
        }

        return $this->modulesInfo;
    }

    /**
     * Load data
     *
     * @return array
     */
    private function load(): array
    {
        $data = [];
        try {
            $url = 'https://mage' . 'fan.com/media/product-versions-extended.json';

            // Make the request
            $this->curl->get($url);

            // Get the response
            $response = $this->curl->getBody();

            if ($response) {
                $this->cache->save($response, self::CACHE_KEY, [], self::CACHE_LIFETIME);
                $data = json_decode($response, true);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $data;
    }

    /**
     * Get cached data
     *
     * @return array
     */
    private function loadFromCache(): array
    {
        $cachedData = $this->cache->load(self::CACHE_KEY);
        if ($cachedData) {
            return json_decode($cachedData, true);
        }

        return [];
    }
}
