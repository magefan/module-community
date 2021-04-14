<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetModuleVersionInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\Module\ModuleListInterface;

class GetModuleVersion implements GetModuleVersionInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var File
     */
    private $file;

    /**
     * @var Reader
     */
    private $moduleReader;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var array
     */
    private $versions = [];

    /**
     * GetModuleVersion constructor.
     * @param SerializerInterface $serializer
     * @param File $file
     * @param Reader $moduleReader
     * @param ModuleListInterface $moduleList
     */
    public function __construct(
        SerializerInterface $serializer,
        File $file,
        Reader $moduleReader,
        ModuleListInterface $moduleList
    ) {
        $this->serializer = $serializer;
        $this->file = $file;
        $this->moduleReader = $moduleReader;
        $this->moduleList = $moduleList;
    }

    /**
     * @param string $moduleName
     * @return string
     */
    public function execute(string $moduleName): string
    {
        if (!isset($this->versions[$moduleName])) {
            $module = $this->moduleList->getOne($moduleName);
            if (!$module) {
                $this->versions[$moduleName] = '';
            } else {
                $fileDir = $this->moduleReader->getModuleDir('', $moduleName) . '/composer.json';
                $data = $this->file->read($fileDir);
                if ($data) {
                    try {
                        $data = $this->serializer->unserialize($data);
                    } catch (\Exception $e) {
                        $data = [];
                    }
                    if (empty($data['version'])) {
                        return !empty($module['setup_version']) ? $module['setup_version'] : '';
                    }
                }

                $this->versions[$moduleName] = !empty($data['version']) ? $data['version'] : '';
            }
        }

        return $this->versions[$moduleName];
    }
}
