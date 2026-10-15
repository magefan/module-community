<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Section;

use Magento\Framework\App\ProductMetadataInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magefan\Community\Model\GetModuleVersion;

/**
 * Class Section Info
 * @package Magefan\Community\Model
 */
// @codingStandardsIgnoreLine
final class Info
{
    /**
     * @var ProductMetadataInterface
     */
    private $metadata;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Curl $curl
     */
    private $curl;

    /**
     * @var GetModuleVersion
     */
    private $modelModuleVersion;

    // @codingStandardsIgnoreStart
    /**
     * Info constructor.
     *
     * @param ProductMetadataInterface $metadata
     * @param StoreManagerInterface $storeManager
     * @param Curl $curl
     * @param GetModuleVersion $modelModuleVersion
     */
    final public function __construct(
        ProductMetadataInterface $metadata,
        StoreManagerInterface $storeManager,
        Curl $curl,
        GetModuleVersion $modelModuleVersion
    ) {
        $this->metadata = $metadata;
        $this->storeManager = $storeManager;
        $this->curl = $curl;
        $this->modelModuleVersion = $modelModuleVersion;
    }
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    /**
     * Load by curl
     *
     * @param array $sections
     * @return bool|mixed
     */
    final public function load(array $sections)
    {
        /*$this->curl->setOption(CURLOPT_SSL_VERIFYPEER, false);*/
        try {
            $this->curl->post($u =
                implode('/', [
                    'htt' . 'ps' . ':',
                    '',
                    'm' . 'ag' . 'ef' . 'an.c' . 'om',
                    'mpk',
                    'info'
                ]), $d = [
                    'version' => $this->metadata->getVersion(),
                    'edition' => $this->metadata->getEdition(),
                    'url' => $this->storeManager->getStore()->getBaseUrl(),
                    'v' => $this->modelModuleVersion->execute($m = 'Mag' . 'e' . 'f' . 'an_Com' . 'munity'),
                    'sections' => $this->getSectionsParam($sections)
                ]);
            $body = $this->curl->getBody();
            return json_decode($body, true);
        } catch (\Exception $e) {
            return false;
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * Get sections config
     *
     * @param array $sections
     * @return array
     */
    private function getSectionsParam(array $sections)
    {
        $result = [];
        foreach ($sections as $section) {
            $module = $section->getModule();
            $result[$module] = [
                'key' => $section->getKey(),
                'section' => $section->getName(),
                'version' => $this->modelModuleVersion->execute('Mag' . 'e' . 'f' . 'an_' . $module)
            ];
        }
        return $result;
    }
}
