<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Plugin\Magento\Backend\Model\Menu;

use Magento\Backend\Model\Menu\Builder;
use Magento\Backend\Model\Menu;
use Magento\Backend\Model\Menu\ItemFactory;
use Magefan\Community\Model\Config;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\Module\Manager;
use Magento\Framework\Module\ModuleListInterface;

class BuilderPlugin
{
    /**
     * @var ItemFactory
     */
    private $menuItemFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var array
     */
    private $configSections;

    /**
     * @var array
     */
    private $magefanModules;

    /**
     * BuilderPlugin constructor.
     *
     * @param ItemFactory $menuItemFactory
     * @param Config $config
     * @param Structure $structure
     * @param ModuleListInterface $moduleList
     * @param Manager $moduleManager
     */
    public function __construct(
        ItemFactory $menuItemFactory,
        Config $config,
        Structure $structure,
        ModuleListInterface $moduleList,
        Manager $moduleManager
    ) {
        $this->menuItemFactory = $menuItemFactory;
        $this->config = $config;
        $this->structure = $structure;
        $this->moduleList = $moduleList;
        $this->moduleManager = $moduleManager;
        $this->magefanModules = $this->getMagefanModules();
    }

    /**
     * @param Builder $subject
     * @param Menu $menu
     * @param $result
     * @return mixed $result
     */
    public function afterGetResult(Builder $subject, Menu $menu, $result)
    {
        $menuEnabled = $this->config->menuEnabled();
        if ($menuEnabled) {
            $item = $this->menuItemFactory->create([
                'data' => [
                    'id' => 'Magefan_Community::elements',
                    'title' => 'Magefan',
                    'module' => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements'
                ]
            ]);
            $menu->add($item, null, 61);
            $subItems = $this->getSubItem($menu->toArray());
            $this->createMenuItem($menu, $subItems, 'Magefan_Community::elements');

            $item = $this->menuItemFactory->create([
                'data' => [
                    'id' => 'Magefan_Community::extension_and_notification',
                    'title' => 'Extensions &amp; Notifications',
                    'module' => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements'
                ]
            ]);
            $menu->add($item, 'Magefan_Community::elements', 1000);

            $item = $this->menuItemFactory->create([
                'data' => [
                    'id' => 'Magefan_Community::extension_and_notification_view',
                    'title' => 'Manage',
                    'module' => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements',
                    'action' => 'adminhtml/system_config/edit/section/mfextension',
                ]
            ]);
            $menu->add($item, 'Magefan_Community::extension_and_notification', 1000);

            unset($this->configSections['Magefan_Community']);

            foreach ($this->magefanModules as $moduleName) {
                $section = $this->getConfigSections($moduleName);
                if ($section) {
                    $item = $this->menuItemFactory->create([
                        'data' => [
                            'id' => $section['resource'],
                            'title' => $section['label'],
                            'module' => $moduleName,
                            'resource' => $section['resource']
                        ]
                    ]);
                    $menu->add($item, 'Magefan_Community::elements');

                    $item = $this->menuItemFactory->create([
                        'data' => [
                            'id' => $section['resource'] . '_menu',
                            'title' => 'Configuration',
                            'resource' => $section['resource'],
                            'action' => 'adminhtml/system_config/edit/section/' . $section['key'],
                            'module' => $moduleName
                        ]
                    ]);
                    $menu->add($item, $section['resource'], 1000);
                }
            }
        }

        return $result;
    }

    /**
     * @param $moduleName
     * @return mixed|null
     */
    private function getConfigSections($moduleName)
    {
        if (null === $this->configSections) {
            $sections = [];
            $this->configSections = [];
            $tabs = $this->structure->getTabs();

            foreach ($tabs as $tab) {
                if ($tab->getId() == 'magefan') {
                    $sections = $tab->getData()['children'];
                    break;
                }
            }

            foreach ($sections as $key => $section) {
                if (empty($section['resource']) || 0 !== strpos($section['resource'], 'Magefan_')) {
                    continue;
                }

                $section['key'] = $key;
                $mName =  $this->getModuleNameByResource($section['resource']);
                $this->configSections[$mName] = $section;
            }
        }

        return isset($this->configSections[$moduleName]) ? $this->configSections[$moduleName] : null;
    }

    /**
     * @param $resource
     * @return string
     */
    private function getModuleNameByResource($resource)
    {
        $moduleName =  explode(':', $resource);
        $moduleName = $moduleName[0];

        return $moduleName;
    }

    /**
     * @param $menu
     * @param $items
     * @param $parentId
     */
    private function createMenuItem($menu, $items, $parentId)
    {
        foreach ($items as $item) {
            $moduleName = $item['module'];
            $title = preg_replace('/(?<!\ )[A-Z]/', ' $0', $moduleName);
            $title = trim(str_replace('Magefan_', '', $title));
            $needCreateMenuItem = ('Magefan_Community::elements' == $parentId && !empty($item['action']));
            if ($needCreateMenuItem) {
                $subItem = $this->menuItemFactory->create([
                    'data' => [
                        'id' => $item['id'] . '3',
                        'title' => $title,
                        'resource' => $item['resource'],
                        'module' => $item['module']
                    ]
                ]);
                $menu->add($subItem, $parentId);
            }

            $subItem = $this->menuItemFactory->create([
                'data' => [
                    'id' => $item['id'] . '2',
                    'title' => $item['title'],
                    'resource' => $item['resource'],
                    'action' => $item['action'],
                    'module' => $item['module']
                ]
            ]);
            if ($needCreateMenuItem) {
                $menu->add($subItem, $item['id'] . '3');
            } else {
                $menu->add($subItem, $parentId);
            }

            if (!empty($item['sub_menu'])) {
                $this->createMenuItem($menu, $item['sub_menu'], $item['id'] . '2');
            }

            if ('Magefan_Community::elements' == $parentId) {
                $addConfig = true;
                if (!empty($item['sub_menu'])) {
                    foreach ($item['sub_menu'] as $subItem) {
                        if ('Configuration' == $subItem['title']) {
                            $addConfig = false;
                            break;
                        }
                    }
                }

                if ($addConfig) {
                    $section = $this->getConfigSections($moduleName);
                    if ($section) {
                        $subItem = $this->menuItemFactory->create([
                            'data' => [
                                'id' => $section['resource'] . '_menu',
                                'title' => 'Configuration',
                                'resource' => $section['resource'],
                                'action' => 'adminhtml/system_config/edit/section/' . $section['key'],
                                'module' => $moduleName
                            ]
                        ]);
                        if ($needCreateMenuItem) {
                            $menu->add($subItem, $item['id'] . '3');
                        } else {
                            $menu->add($subItem, $item['id'] . '2');
                        }
                    }
                }
            }
            unset($this->configSections[$moduleName]);
            unset($this->magefanModules[array_search($moduleName, $this->magefanModules)]);
        }
    }

    /**
     * @param $items
     * @return array
     */
    private function getSubItem($items)
    {
        $subItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                if (isset($item['module']) &&  0 === strpos($item['module'], 'Magefan_')) {
                    if ('Magefan_Community::elements' != $item['id']) {
                        $subItems[] = $item;
                    }
                } elseif (!empty($item['sub_menu'])) {
                    $subItems = array_merge($subItems, $this->getSubItem($item['sub_menu']));
                }
            }
        }

        return $subItems;
    }

    /**
     * Retrieve Magefan modules info
     *
     * @return array
     */
    private function getMagefanModules()
    {
        $modules = [];
        foreach ($this->moduleList->getNames() as $moduleName) {
            if (strpos($moduleName, 'Magefan_') !== false && $this->moduleManager->isEnabled($moduleName)) {
                $modules[] = $moduleName;
            }
        }
        return $modules;
    }
}
