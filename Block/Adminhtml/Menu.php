<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magefan\Community\Model\Menu\MagefanGroupsProvider;
use Magento\Backend\Block\Template;
use Magento\Framework\Serialize\Serializer\Json;

class Menu extends Template
{
    /**
     * @var MagefanGroupsProvider
     */
    private $groupsProvider;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Template\Context $context
     * @param MagefanGroupsProvider $groupsProvider
     * @param Json $serializer
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        MagefanGroupsProvider $groupsProvider,
        Json $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->groupsProvider = $groupsProvider;
        $this->serializer = $serializer;
    }

    /**
     * Get Groups Json
     * @return string
     */
    public function getGroupsJson(): string
    {
        $groups = [];
        foreach ($this->groupsProvider->get() as $group) {
            $modules = [];
            foreach ($group['extensions'] as $moduleName) {
                $modules[] = $this->moduleNameToPrefix($moduleName);
            }
            $groups[] = [
                'name' => $group['name'],
                'base' => isset($group['base']) ? $this->moduleNameToPrefix($group['base']) : null,
                'modules' => $modules,
            ];
        }

        return $this->serializer->serialize($groups);
    }

    /**
     * Convert Magento module name to data-ui-id prefix.
     * Magefan_Seo => menu-magefan-seo-
     * @param string $moduleName
     * @return string
     */
    private function moduleNameToPrefix(string $moduleName): string
    {
        return 'menu-' . strtolower(str_replace('_', '-', $moduleName)) . '-';
    }
}
