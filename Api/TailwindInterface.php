<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

interface TailwindInterface
{
    /**
     * Retrieve Tailwind classes by key.
     *
     * @param string $key
     * @return string
     */
    public function get(string $key): string;

    /**
     * Retrieve escaped for HTML attribute classes with Tailwind classes of each of them added on Hyva themes.
     *
     * @param string $class
     * @return string
     */
    public function getClass(string $class): string;

    /**
     * Retrieve escaped for HTML attribute classes with Tailwind classes of each of them added on Hyva themes.
     *
     * @param string $class
     * @return string
     */
    public function __invoke(string $class): string;
}
