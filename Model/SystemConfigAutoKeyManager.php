<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Store\Model\ScopeInterface;

class SystemConfigAutoKeyManager
{

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var GetModuleVersion
     */
    private $getModuleVersion;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SectionFactory
     */
    private $sectionFactory;

    /**
     * @param WriterInterface $configWriter
     * @param GetModuleVersion $getModuleVersion
     * @param ModuleManager $moduleManager
     * @param ScopeConfigInterface $scopeConfig
     * @param SectionFactory $sectionFactory
     */
    public function __construct(
        WriterInterface $configWriter,
        GetModuleVersion $getModuleVersion,
        ModuleManager $moduleManager,
        ScopeConfigInterface  $scopeConfig,
        SectionFactory $sectionFactory
    ) {
        $this->configWriter = $configWriter;
        $this->getModuleVersion = $getModuleVersion;
        $this->moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
        $this->sectionFactory = $sectionFactory;
    }

    /**
     * @param string $section
     * @param string $key
     * @return void
     */
    public function execute(string $section, string $key) {
        $sections = $this->moduleManager->getSectionByName($section);

        if ($sections) {
            foreach ($sections as $section) {
                $sectionData = $this->sectionFactory->create(['name' => $section]);
                $alreadyExist = $this->scopeConfig->getValue(
                    $sectionData->getName() . '/g'.'en'.'er'.'al'.'/k'.'e'.'y',
                    ScopeInterface::SCOPE_STORE
                );

                if ($sectionData->getModule() && !$alreadyExist) {
                    $this->configWriter->save($section . '/g'.'en'.'er'.'al'.'/k'.'e'.'y', $key);
                }
            }
        }
    }
}