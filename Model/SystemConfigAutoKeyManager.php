<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\App\Config\Storage\WriterInterface;

class SystemConfigAutoKeyManager
{

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ModulePool
     */
    private $modulePool;

    /**
     * @var GetModuleVersion
     */
    private $getModuleVersion;

    /**
     * @param WriterInterface $configWriter
     * @param GetModuleVersion $getModuleVersion
     * @param ModulePool $modulePool
     */
    public function __construct(
        WriterInterface $configWriter,
        GetModuleVersion $getModuleVersion,
        ModulePool $modulePool
    ) {
        $this->configWriter = $configWriter;
        $this->getModuleVersion = $getModuleVersion;
        $this->modulePool = $modulePool;
    }

    /**
     * @param string $section
     * @param string $key
     * @return void
     */
    public function execute(string $section, string $key) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $moduleData = $this->modulePool->getTemplate($section);
        $moduleName = $objectManager->create(Section::class, ['name' => $section])->getModuleName(true);

        $sections = [];
        if ($moduleData) {
            foreach ($moduleData as $plan => $data) {
                if ($plan == 'base' || $this->getModuleVersion->execute('Magefan_' . $moduleName . ucfirst($plan))) {
                    $sections = array_merge(
                        $sections,
                        is_string($data) ? array_map('trim', explode(',', $data)) : (array) $data
                    );
                }
            }
        }
        if ($sections) {
            foreach ($sections as $section) {
                $sectionData = $objectManager->create(Section::class, ['name' => $section]);
                if ($sectionData->getModule()) {
                    $this->configWriter->save($section . '/g'.'en'.'er'.'al'.'/k'.'e'.'y', $key);
                }
            }
        }
    }
}