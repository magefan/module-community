<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\HyvaThemeDetectionInterface;
use Magefan\Community\Api\GetClassesInterface;
use Magefan\Community\Model\GetClasses\ClassesManager;
use Magento\Framework\Escaper;

class GetClasses implements GetClassesInterface
{
    /**
     * @var ClassesManager
     */
    private $classesManager;

    /**
     * @var HyvaThemeDetectionInterface
     */
    private $hyvaThemeDetection;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ClassesManager $classesManager
     * @param HyvaThemeDetectionInterface $hyvaThemeDetection
     * @param Escaper $escaper
     */
    public function __construct(
        ClassesManager $classesManager,
        HyvaThemeDetectionInterface $hyvaThemeDetection,
        Escaper $escaper
    ) {
        $this->classesManager = $classesManager;
        $this->hyvaThemeDetection = $hyvaThemeDetection;
        $this->escaper = $escaper;
    }

    /**
     * Retrieve escaped for HTML attribute classes with Tailwind classes of each of them added on Hyva themes.
     *
     * @param string $class
     * @return string
     */
    public function getClass(string $class): string
    {
        if (!$this->hyvaThemeDetection->execute()) {
            return $this->escaper->escapeHtmlAttr($class);
        }

        $classes = preg_split('/\s+/', trim($class), -1, PREG_SPLIT_NO_EMPTY);
        $classesMap = $this->classesManager->getClasses();

        foreach ($classes as $key) {
            if (!empty($classesMap[$key])) {
                $classes[] = $classesMap[$key];
            }
        }

        return $this->escaper->escapeHtmlAttr(implode(' ', $classes));
    }

    /**
     * Retrieve escaped for HTML attribute classes with Tailwind classes of each of them added on Hyva themes.
     *
     * @param string $class
     * @return string
     */
    public function __invoke(string $class): string
    {
        return $this->getClass($class);
    }
}
