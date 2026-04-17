<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml\System\Config;

use Magefan\Community\Model\Menu\MagefanGroupsProvider;
use Magento\Backend\Block\Template;
use Magento\Config\Model\Config\Structure\Element\Section;
use Magento\Config\Model\Config\Structure;

class Tabs extends Template
{
    public const EXTENSIONS = 'extensions';

    public const ITEM_TYPE = 'type';
    public const TYPE_GROUP = 'group';
    public const TYPE_SINGLE = 'single';

    public const ITEM_NAME = 'name';
    public const ITEM_CLASS = 'class';
    public const ITEM_URL = 'url';
    public const IS_ACTIVE = 'is_active';
    public const SORT_ORDER = 'sort_order';

    /**
     * @var string
     */
    protected $_template = 'Magefan_Community::magefan-config-tabs.phtml';

    /**
     * @var MagefanGroupsProvider
     */
    private $groupsProvider;

    /**
     * @var Structure
     */
    private $configStructure;

    /**
     * @var mixed
     */
    private $currentSectionId;

    /**
     * @param Template\Context $context
     * @param MagefanGroupsProvider $groupsProvider
     * @param Structure $configStructure
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        MagefanGroupsProvider $groupsProvider,
        Structure $configStructure,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->groupsProvider = $groupsProvider;
        $this->configStructure = $configStructure;
        $this->currentSectionId = $this->getRequest()->getParam('section');
    }

    /**
     * @return array[]
     */
    public function getConfigData(): array
    {
        return $this->buildConfigStructure();
    }

    /**
     * Build a flat A-Z sorted list of groups and standalone extensions.
     * @return array[]
     */
    private function buildConfigStructure(): array
    {
        $allActive = $this->fetchMagefanSections();
        $assignedModules = [];
        $items = [];

        foreach ($this->groupsProvider->get() as $group) {
            list($groupItems, $baseItem, $assigned) = $this->buildGroupItems($allActive, $group);
            $assignedModules = array_merge($assignedModules, $assigned);

            if (empty($groupItems) && $baseItem === null) {
                continue;
            }

            $this->applySortOrder($groupItems);

            if ($baseItem !== null) {
                array_unshift($groupItems, $baseItem);
            }

            $items[] = [
                self::ITEM_TYPE  => self::TYPE_GROUP,
                self::ITEM_NAME  => (string)__($group[self::ITEM_NAME]),
                self::EXTENSIONS => $groupItems,
            ];
        }

        foreach ($this->excludeGroupedExtensions($allActive, $assignedModules) as $ext) {
            $ext[self::ITEM_TYPE] = self::TYPE_SINGLE;
            $items[] = $ext;
        }

        usort($items, function ($a, $b) {
            return strcmp($a[self::ITEM_NAME], $b[self::ITEM_NAME]);
        });

        return ['items' => $items];
    }

    /**
     * Extract sub-items and base item for a single group config entry.
     * Returns [groupItems, baseItem|null, assignedModuleNames].
     * @param array $allActive
     * @param array $group
     * @return array
     */
    private function buildGroupItems(array $allActive, array $group): array
    {
        $groupItems = [];
        $baseItem = null;
        $assigned = [];
        $baseModule = $group['base'] ?? null;

        foreach ($group[self::EXTENSIONS] as $moduleName) {
            if (!isset($allActive[$moduleName])) {
                continue;
            }

            $item = $allActive[$moduleName];
            $assigned[] = $moduleName;

            if ($baseModule && $moduleName === $baseModule) {
                $item[self::ITEM_NAME] = (string)__('General');
                $baseItem = $item;
            } else {
                $groupItems[] = $item;
            }
        }

        return [$groupItems, $baseItem, $assigned];
    }

    /**
     * Filters out extensions already assigned to groups using hash map lookups
     * @param array $allExtensions
     * @param array $assignedModules
     * @return array
     */
    private function excludeGroupedExtensions(array $allExtensions, array $assignedModules): array
    {
        $removeMap = array_flip(array_unique($assignedModules));
        $filtered = [];

        foreach ($allExtensions as $key => $value) {
            if (!isset($removeMap[$key])) {
                $filtered[] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Map Magento sections to internal data array
     * @return array
     */
    private function fetchMagefanSections(): array
    {
        $output = [];
        $nodes = $this->getMagefanConfigChildrenNode();

        if ($nodes) {
            foreach ($nodes as $section) {
                if (!$section->isVisible()) {
                    continue;
                }

                $resource = (string)$section->getAttribute('resource');
                $moduleName = current(explode('::', $resource));
                $output[$moduleName] = $this->mapSectionToData($section);
            }
        }

        return $output;
    }

    /**
     * Extract attributes from Section element
     * @param Section $section
     * @return array
     */
    private function mapSectionToData(Section $section): array
    {
        return [
            self::ITEM_NAME => $this->resolveLabel($section),
            self::ITEM_CLASS => (string)$section->getClass(),
            self::ITEM_URL => $this->generateUrl($section),
            self::IS_ACTIVE => $section->getId() === $this->currentSectionId,
            self::SORT_ORDER => (int)$section->getAttribute('sortOrder'),
        ];
    }

    /**
     * @param Section $section
     * @return string
     */
    private function resolveLabel(Section $section): string
    {
        $label = $section->getLabel() ? (string)__($section->getLabel()) : '';
        return $this->_escaper->escapeHtml($label);
    }

    /**
     * @param Section $section
     * @return string
     */
    private function generateUrl(Section $section): string
    {
        return $this->getUrl('*/*/*', [
            '_current' => true,
            'section' => $section->getId()
        ]);
    }

    /**
     * @param array $list
     * @return void
     */
    private function applySortOrder(array &$list): void
    {
        usort($list, function ($a, $b) {
            $sortA = $a[self::SORT_ORDER];
            $sortB = $b[self::SORT_ORDER];

            if ($sortA == $sortB) {
                return 0;
            }
            return ($sortA < $sortB) ? -1 : 1;
        });
    }

    /**
     * @return null
     */
    public function getMagefanConfigChildrenNode()
    {
        $configTabs = $this->configStructure->getTabs();
        foreach ($configTabs as $node) {
            if ($node->getId() == 'magefan') {
                return $node->getChildren();
            }
        }

        return null;
    }
}
