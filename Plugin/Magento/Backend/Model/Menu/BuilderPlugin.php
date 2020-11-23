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
     * BuilderPlugin constructor.
     *
     * @param ItemFactory $menuItemFactory
     * @param Config $config
     */
    public function __construct(
        ItemFactory $menuItemFactory,
        Config $config
    ) {
        $this->menuItemFactory = $menuItemFactory;
        $this->config = $config;
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
                    'module '=> 'Magefan_Community',
                    'resource' => 'Magefan_Community::elements'
                ]
            ]);
            $menu->add($item, null, 20);
            // add submenu for the menu item added above
            /*
            foreach (loop through my dynamic list as $dynamicItem) {
                $item = $this->menuItemFactory->create([
                    'data' => [
                        'parent_id' => '[Vendor]_[Module]::some_key_here', //id of menu above
                        'id' => '[Vendor]_[Module]::some_key_here_'.$dynamicItem->getCode(), //give it a unique id
                        'title' => $dynamicItem->getTitle(), //title of the submenu
                        'resource' => '[Vendor]_[Module]::some_key_here', //same ACL key as above, or it can be different
                        'action' => $dynamicItem->getUrl() //url for the main menu
                    ]
                ]);
                $menu->add($item, [Vendor]_[Module]::some_key_here'); //add is as a child for the menu item above
            }
            */
        }
        return $result;
    }
}
