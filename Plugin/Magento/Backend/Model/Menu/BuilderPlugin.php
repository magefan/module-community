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
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * BuilderPlugin constructor.
     *
     * @param ItemFactory $menuItemFactory
     * @param Config $config
     * @param ModuleListInterface $moduleList
     * @param Manager $moduleManager
     */
    public function __construct(
        ItemFactory $menuItemFactory,
        Config $config,
        ModuleListInterface $moduleList,
        Manager $moduleManager
    ) {
        $this->menuItemFactory = $menuItemFactory;
        $this->config = $config;
        $this->moduleList = $moduleList;
        $this->moduleManager = $moduleManager;
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
            $menu->add($item, null, 20);

            $subItems = $this->getSubItem($menu->toArray());
            $this->createMenuItem($menu, $subItems, 'Magefan_Community::elements');
        }

        return $result;
    }

    private function createMenuItem($menu, $items, $parentId)
    {
        foreach ($items as $item) {
            if ('Magefan_Community::elements' == $parentId && !empty($item['action'])) {
                $subItem = $this->menuItemFactory->create([
                    'data' => [
                        'id' => $item['id'] . '3',
                        'title' => str_replace('Magefan_', '', $item['module']),
                        'resource' => $item['resource'],
                        'module' => $item['module']
                    ]
                ]);

                $menu->add($subItem, $parentId);
                $parentId = $item['id'] . '3';
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

            $menu->add($subItem, $parentId);

            if (!empty($item['sub_menu'])) {
                $this->createMenuItem($menu, $item['sub_menu'], $item['id'] . '2');
            }
        }
    }

    private function getSubItem($items)
    {
        $subItems = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                if (0 === strpos($item['module'], 'Magefan_')) {
                    $subItems[] = $item;
                } elseif (!empty($item['sub_menu'])) {
                    $subItems = array_merge($subItems, $this->getSubItem($item['sub_menu']));
                }
            }
        }

        return $subItems;
    }
}
