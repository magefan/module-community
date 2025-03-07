<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

class ModulePool
{
    /**
     * Modules objects
     *
     * @var []
     */
    private $modules;

    /**
     * ModulePool constructor.
     * @param array $modules
     */
    public function __construct(array $modules)
    {
        $this->modules = $modules;
    }

    /**
     * @return array
     */
    public function getAll():array
    {
        $structuredModules = [];

        foreach ($this->modules as $parentModule => $childModules) {
            foreach ($childModules as $childModule) {
                $structuredModules[$parentModule] = array_merge(
                    $structuredModules[$parentModule] ?? [],
                    array_map('trim', explode(',', $childModule))
                );
            }
        }

        return $structuredModules ?? [];
    }

    /**
     * @param string $section
     * @return string
     */
    public function getTemplate(string $section)
    {
        if (isset($this->modules[$section]) &&
            (is_array($this->modules[$section]) || $this->modules[$section] instanceof Countable)
        ) {
            return $this->modules[$section];
        }
        return null;
    }
}
