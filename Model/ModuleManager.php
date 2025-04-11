<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

class ModuleManager
{
    private $moduleManager = [
        'mfseo' => [
            'plus' => ['mfrichsnippets','mfxmlsitemap','mfhs'],
            'extra' => ['alternatehreflang','mfogt','mftwittercards']
        ],
        'mfspeedoptimizations' => [
            'base' => ['mflazyzoad','mfrocketjavascript'],
            'plus' => ['mfwebp'],
            'extra' => ['mfpagecachewarmer']
        ],
    ];

    /**
     * @var GetModuleVersion
     */
    private $getModuleVersion;

    /**
     * @var SectionFactory
     */
    private $sectionFactory;

    /**
     * @param GetModuleVersion $getModuleVersion
     * @param SectionFactory $sectionFactory
     */
    public function __construct(
        GetModuleVersion $getModuleVersion,
        SectionFactory $sectionFactory
    )
    {
        $this->getModuleVersion = $getModuleVersion;
        $this->sectionFactory = $sectionFactory;
    }

    /**
     * @return array
     */
    public function getAllSections()
    {
        $allInstModule = [];
        foreach ($this->moduleManager as $section => $plans) {
            $extensionName = $this->sectionFactory->create(['name' => $section])->getModuleName();
            $extensionName = str_replace(['Extra','Plus'],'', $extensionName);
            foreach ($plans as $key => $modules) {
                if ($key == 'base' ||$this->getModuleVersion->execute('Magefan_' . $extensionName . ucfirst($key))) {
                    $allInstModule[$section] = array_merge($allInstModule[$section] ?? [], $modules);
                }
            }
        }

        return $allInstModule;
    }

    /**
     * @param $name
     * @return array|null
     */
    public function getSectionByName($name)
    {
        if (isset($this->moduleManager[$name])) {
            $sections = [];
            foreach ($this->moduleManager[$name] as $plan => $data) {
                foreach ($data as $section) {
                    $extensionName = $this->sectionFactory->create(['name' => $section])->getModuleName();
                    $extensionName = str_replace(['Extra','Plus'],'', $extensionName);
                    if ($plan == 'base' || $this->getModuleVersion->execute('Magefan_' . $extensionName . ucfirst($plan))) {
                        $sections[] = $section;
                    }
                }

            }
            return $sections;
        }
        return null;
    }
}