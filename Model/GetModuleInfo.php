<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Client\CurlFactory;
use Magento\Framework\App\CacheInterface;
use Magefan\Community\Api\GetModuleInfoInterface;
use Psr\Log\LoggerInterface;

class GetModuleInfo implements GetModuleInfoInterface
{
    public const CACHE_KEY = 'magefan_product_versions_extended_json';
    public const CACHE_LIFETIME = 43200;

    /**
     * Cache key of the flag that prevents remote requests until it expires
     */
    public const CHECK_CACHE_KEY = 'magefan_product_versions_extended_json_check';

    /**
     * Lifetime of the last valid response, used as fallback when magefan.com is unreachable
     */
    public const DATA_CACHE_LIFETIME = 2592000;

    /**
     * Delay before the next attempt after a failed request
     */
    public const RETRY_LIFETIME = 3600;

    /**
     * Max time in seconds to establish connection
     */
    public const CONNECT_TIMEOUT = 2;

    /**
     * Max time in seconds for the whole request
     */
    public const TIMEOUT = 3;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

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
     * @param CurlFactory $curlFactory
     * @param CacheInterface $cache
     * @param LoggerInterface $logger
     */
    public function __construct(
        CurlFactory $curlFactory,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->curlFactory = $curlFactory;
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
        if ($this->cache->load(self::CHECK_CACHE_KEY)) {
            /* Data was loaded recently or previous request failed, use cache */
            return [];
        }

        $data = [];
        try {
            $url = 'https://mage' . 'fan.com/media/product-versions-extended.json';

            $curl = $this->curlFactory->create();
            $curl->setTimeout(self::TIMEOUT);
            $curl->setOption(CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);

            // Make the request
            $curl->get($url);

            // Get the response
            $response = $curl->getBody();

            if ($response && 200 == $curl->getStatus()) {
                $data = json_decode($response, true);
                if (is_array($data) && $data) {
                    $this->cache->save($response, self::CACHE_KEY, [], self::DATA_CACHE_LIFETIME);
                } else {
                    $data = [];
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        $this->cache->save('1', self::CHECK_CACHE_KEY, [], $data ? self::CACHE_LIFETIME : self::RETRY_LIFETIME);

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
            $data = json_decode($cachedData, true);
            if (is_array($data)) {
                return $data;
            }
        }

        return [];
    }
}
