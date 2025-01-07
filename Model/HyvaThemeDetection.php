<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\HyvaThemeDetectionInterface;
class HyvaThemeDetection extends AbstractThemeDetection implements HyvaThemeDetectionInterface
{
    /**
     * @return string
     */
    public function getThemeModuleName(): string
    {
        return 'Hy' . 'v' . 'a_T' . 'he' . 'me';
    }

    /**
     * @return string
     */
    public function getThemeName(): string
    {
        return 'h' . 'y' . 'v' . 'a';
    }
}
