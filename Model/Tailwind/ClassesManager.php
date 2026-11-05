<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Tailwind;

use Magefan\Community\Model\Cache\Type\Tailwind as TailwindCache;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Framework\View\DesignInterface;

class ClassesManager
{
    /**
     * Path of the classes file inside module view/frontend directory and theme module directory.
     */
    public const FILE_PATH = 'tailwind/tailwind.classes';

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var DesignInterface
     */
    private $design;

    /**
     * @var TailwindCache
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var File
     */
    private $fileDriver;

    /**
     * @var array
     */
    private $classes = [];

    /**
     * @param ModuleListInterface $moduleList
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param DesignInterface $design
     * @param TailwindCache $cache
     * @param SerializerInterface $serializer
     * @param File $fileDriver
     */
    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        DesignInterface $design,
        TailwindCache $cache,
        SerializerInterface $serializer,
        File $fileDriver
    ) {
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->design = $design;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Retrieve merged Tailwind classes of all modules and the current theme.
     *
     * @return array
     */
    public function getClasses(): array
    {
        $theme = $this->design->getDesignTheme();
        $themePath = $theme ? (string)$theme->getFullPath() : '';

        if (!isset($this->classes[$themePath])) {
            $cacheKey = TailwindCache::CACHE_TAG . '_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $themePath);
            $cached = $this->cache->load($cacheKey);
            $classes = $cached ? $this->serializer->unserialize($cached) : null;

            if (!is_array($classes)) {
                $classes = $this->collect($theme);
                $this->cache->save($this->serializer->serialize($classes), $cacheKey);
            }

            $this->classes[$themePath] = $classes;
        }

        return $this->classes[$themePath];
    }

    /**
     * Read and merge classes files: modules first, then themes from parent to child.
     *
     * @param ThemeInterface|null $theme
     * @return array
     */
    private function collect(?ThemeInterface $theme): array
    {
        $moduleNames = $this->moduleList->getNames();
        $files = [];

        foreach ($moduleNames as $moduleName) {
            $modulePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
            if ($modulePath) {
                $files[] = $modulePath . '/view/frontend/' . self::FILE_PATH;
            }
        }

        foreach ($this->getThemePaths($theme) as $themeDir) {
            foreach ($moduleNames as $moduleName) {
                $files[] = $themeDir . '/' . $moduleName . '/' . self::FILE_PATH;
            }
            $files[] = $themeDir . '/web/' . self::FILE_PATH;
        }

        $classes = [];
        foreach ($files as $file) {
            try {
                if (!$this->fileDriver->isFile($file)) {
                    continue;
                }
                $classes = array_replace($classes, $this->parse((string)$this->fileDriver->fileGetContents($file)));
            } catch (\Exception $e) {
                continue;
            }
        }

        return $classes;
    }

    /**
     * Retrieve directories of the theme and its parents, parent first.
     *
     * @param ThemeInterface|null $theme
     * @return array
     */
    private function getThemePaths(?ThemeInterface $theme): array
    {
        $paths = [];
        while ($theme) {
            $path = $this->componentRegistrar->getPath(ComponentRegistrar::THEME, (string)$theme->getFullPath());
            if ($path) {
                array_unshift($paths, $path);
            }
            $theme = $theme->getParentTheme();
        }

        return $paths;
    }

    /**
     * Parse classes file content in format: key { classes }.
     *
     * @param string $content
     * @return array
     */
    private function parse(string $content): array
    {
        $content = (string)preg_replace('#/\*.*?\*/#s', '', $content);
        preg_match_all('/([^\s{}]+)\s*\{([^{}]*)\}/', $content, $matches, PREG_SET_ORDER);

        $result = [];
        foreach ($matches as $match) {
            $result[$match[1]] = implode(' ', preg_split('/\s+/', trim($match[2]), -1, PREG_SPLIT_NO_EMPTY));
        }

        return $result;
    }
}
