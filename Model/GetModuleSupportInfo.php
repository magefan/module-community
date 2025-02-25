<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\Client\Curl;
use Magefan\Community\Api\GetModuleSupportInfoInterface;

class GetModuleSupportInfo implements GetModuleSupportInfoInterface
{
    const CACHE_KEY = 'mfs_cache';
    const CACHE_LIFE_TIME = '-1 month';

    /**
     * @var Curl
     */
    private $curl;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @param Curl $curl
     * @param ResourceConnection $resource
     */
    public function __construct(
        Curl $curl,
        ResourceConnection $resource
    ) {
        $this->curl = $curl;
        $this->resource = $resource;
    }

    /**
     * @param $moduleData
     * @return string|null
     */
    private function loadFromCache($moduleData)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::CACHE_KEY);

        $select = $connection->select()
            ->from($table, ['data'])
            ->where('module_name LIKE ?', $moduleData['name'])
            ->where('updated_at >= ?', date('Y-m-d H:i:s', strtotime(self::CACHE_LIFE_TIME)));

        $data = $connection->fetchOne($select);
        if (!$data) {
            try {
                $url = 'http://magefan.loc/mpk/info/support';
                $this->curl->post($url, ['key' => $moduleData['key']]);
                $response = $this->curl->getBody();
                $responseData = json_decode($response, true);
                if ($response && !isset($responseData['error'])) {
                    $this->updateCache($moduleData['name'], $response);
                    return $response;
                }
            } catch (\Exception $e) {

            }
        }

        return $data;
    }


    /**
     * @param string $moduleName
     * @param string $response
     * @return void
     */
    private function updateCache(string $moduleName, string $response)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::CACHE_KEY);
        $select = $connection->select()
            ->from($table, ['id'])
            ->where('module_name = ?', $moduleName);

        $exists = $connection->fetchOne($select);

        if ($exists) {
            $connection->update($table, ['data' => $response], ['module_name = ?' => $moduleName]);
        } else {
            $connection->insert($table, ['module_name' => $moduleName, 'data' => $response]);
        }
    }

    /**
     * @param array $moduleData
     * @return bool
     */
    public function validSupport(array $moduleData): bool
    {
        $moduleSupportInfo = $this->loadFromCache($moduleData);

        if ($moduleSupportInfo) {
            $decodedData = json_decode($moduleSupportInfo, true);
            return !empty($decodedData['data']) && $decodedData['data'] < date('Y-m-d H:i:s', strtotime('-1 year'));
        }

        return false;
    }
}
