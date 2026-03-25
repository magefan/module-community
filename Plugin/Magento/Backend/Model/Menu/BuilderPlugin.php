<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Backend\Model\Menu;

use Magefan\Community\Api\GetModuleInfoInterface;
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
     * @var GetModuleInfoInterface
     */
    private $getModuleInfo;

    /**
     * BuilderPlugin constructor.
     * @param ItemFactory $menuItemFactory
     * @param Config $config
     * @param Structure $structure
     * @param ModuleListInterface $moduleList
     * @param Manager $moduleManager
     * @param GetModuleInfoInterface $getModuleInfo
     */
    public function __construct(
        ItemFactory $menuItemFactory,
        Config $config,
        Structure $structure,
        ModuleListInterface $moduleList,
        Manager $moduleManager,
        GetModuleInfoInterface $getModuleInfo
    ) {
        $this->menuItemFactory = $menuItemFactory;
        $this->config = $config;
        $this->structure = $structure;
        $this->moduleList = $moduleList;
        $this->moduleManager = $moduleManager;
        $this->magefanModules = $this->getMagefanModules();
        $this->getModuleInfo = $getModuleInfo;
    }

    /**
     * Add custom data to result
     *
     * @param Builder $subject
     * @param Menu $menu
     * @param Menu $result
     * @return mixed $result
     */
    public function afterGetResult(Builder $subject, Menu $menu, $result)
    {
        $menuEnabled = $this->config->menuEnabled();
        if ($menuEnabled) {
            $modulesInfo = $this->getModuleInfo->execute();

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

            $item = $this->menuItemFactory->create([
                'data' => [
                    'id'       => 'Magefan_Community::magefan_extensions',
                    'title'    => 'Magefan Marketplace',
                    'module'   => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements',
                ]
            ]);
            $menu->add($item, 'Magefan_Community::elements', 6000);

            $item = $this->menuItemFactory->create([
                'data' => [
                    'id'       => 'Magefan_Community::magefan_extensions_child',
                    'title'    => 'Extensions',
                    'module'   => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements',
                    'action'   => 'adminhtml/system_config/edit/section/mfextension',
                ]
            ]);
            $menu->add($item, 'Magefan_Community::magefan_extensions', 10);

            $item = $this->menuItemFactory->create([
                'data' => [
                    'id'       => 'Magefan_Community::magefan_user_guides',
                    'title'    => 'Magefan User Guides',
                    'module'   => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements',
                ]
            ]);
            $menu->add($item, 'Magefan_Community::elements', 6000);

            $item = $this->menuItemFactory->create([
                'data' => [
                    'id'       => 'Magefan_Community::magefan_user_guides_child',
                    'title'    => 'User Guides',
                    'module'   => 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements',
                    'action'   => 'adminhtml/system_config/edit/section/mfextension',
                ]
            ]);
            $menu->add($item, 'Magefan_Community::magefan_user_guides', 10);

            unset($this->configSections['Magefan_Community']);

            foreach ($this->magefanModules as $moduleName) {
                $section = $this->getConfigSections($moduleName);

                if (isset($section['id']) && 'mfextension' != $section['id']) {
                    $item = $this->menuItemFactory->create([
                        'data' => [
                            'id' => $section['resource'] . '_custom',
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
                    $menu->add($item, $section['resource'] . '_custom', 1000);
                }
            }

            try {
                $added = []; // track already processed parents to avoid duplicates

                foreach ($menu as $item) {
                    if ($item->hasChildren()) {
                        foreach ($item->getChildren() as $children) {
                            $id = $children->getId();
                            // check if id starts with Magefan_ but skip Magefan_Community itself
                            if (strpos($id, 'Magefan_') !== 0) {
                                continue;
                            }
                            if (strpos($id, 'Magefan_Community::elements') === 0) {
                                continue;
                            }

                            if (in_array($id, $added)) {
                                continue;
                            }

                            $added[] = $id;

                            // extract module name from id e.g. Magefan_Blog::elements -> Magefan_Blog
                            $module = explode('::', $id)[0];
                            $module = explode('_', $module)[1];
                            $url = !empty($modulesInfo[$module]) ? $modulesInfo[$module]->getDocumentationUrl() : '';
                            // unique id per module to avoid conflicts
                            $newItemId = $id . '_user_guides';
                            if (!$url) {
                                continue;
                            }

                            try {
                                $encodedUrl = 'mf-ug-url-start' . base64_encode($url) . 'mf-ug-url-end';

                                $userGuideItem = $this->menuItemFactory->create([
                                    'data' => [
                                        'id'       => $newItemId,
                                        'title'    => 'User Guides',
                                        'module'   => 'Magefan_Community',
                                        'resource' => 'Magefan_Community::elements',
                                        'action'   => $encodedUrl,
                                    ]
                                ]);

                                $menu->add($userGuideItem, $id, 6000);

                            } catch (\Exception $e) {
                            }
                        }
                    }
                }

            } catch (\Exception $e) {
            }
        }

        return $menu;
    }

    /**
     * Ge config by module name
     *
     * @param string $moduleName
     * @return mixed|null
     */
    private function getConfigSections($moduleName)
    {
        if (null === $this->configSections) {
            $sections = [];
            $this->configSections = [];

            try {
                $tabs = $this->structure->getTabs();
            } catch (\Exception $e) {
                $tabs = [];
            }

            foreach ($tabs as $tab) {
                if (in_array($tab->getId(), ['magefan', 'mf_extensions_list'])) {
                    // phpcs:ignore Magento2.Performance.ForeachArrayMerge
                    $sections = array_merge($sections, $tab->getData()['children']);
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
     * Get module by resource
     *
     * @param string $resource
     * @return string
     */
    private function getModuleNameByResource($resource)
    {
        $moduleName =  explode(':', $resource);
        $moduleName = $moduleName[0];

        return $moduleName;
    }

    /**
     * Create menu item
     *
     * @param Menu $menu
     * @param array $items
     * @param int $parentId
     */
    private function createMenuItem($menu, $items, $parentId)
    {
        foreach ($items as $item) {
            $moduleName = isset($item['module']) ? $item['module'] : null;
            $title = preg_replace('/(?<!\ )[A-Z]/', ' $0', $moduleName);
            $title = trim(str_replace('Magefan_', '', $title));
            $needCreateMenuItem = ('Magefan_Community::elements' == $parentId && !empty($item['action']));
            if ($needCreateMenuItem) {
                $subItem = $this->menuItemFactory->create([
                    'data' => [
                        'id' => $item['id'] . '3',
                        'title' => $title,
                        'resource' => $item['resource'],
                        'module' => isset($item['module']) ? $item['module'] : null,
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
                    'module' => isset($item['module']) ? $item['module'] : null,
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
            $index = array_search($moduleName, $this->magefanModules);
            if (false !== $index) {
                unset($this->magefanModules[$index]);
            }
        }
    }

    /**
     * Add sub menu item
     *
     * @param array $items
     * @return array
     */
    private function getSubItem($items)
    {
        $subItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                if (isset($item['module']) && 0 === strpos($item['module'], 'Magefan_')
                    || !isset($item['module']) && isset($item['id']) && 0 === strpos($item['id'], 'Magefan_')
                ) {
                    if ('Magefan_Community::elements' != $item['id']) {
                        $subItems[] = $item;
                    }
                } elseif (!empty($item['sub_menu'])) {
                    // phpcs:ignore Magento2.Performance.ForeachArrayMerge
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
