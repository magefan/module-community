<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Menu;

class MagefanGroupsProvider
{
    /**
     * @var array
     */
    private $groups;

    /**
     * @param array $groups
     */
    public function __construct(
        array $groups = []
    ) {
        $this->groups = $groups;
    }

    /**
     * Returns groups config.
     * Each group: ['name' => string, 'extensions' => [ModuleName, ...]]
     *
     * @return array
     */
    public function get(): array
    {
        return $this->groups;
    }
}
