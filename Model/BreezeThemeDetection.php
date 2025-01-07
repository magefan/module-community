<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\BreezeThemeDetectionInterface;
class BreezeThemeDetection extends AbstractThemeDetection implements BreezeThemeDetectionInterface
{
    /**
     * @return string
     */
    public function getThemeModuleName(): string
    {
        return 'Swi'.'ssu'.'p_B'.'re'.'eze';
    }

    /**
     * @return string
     */
    public function getThemeName(): string
    {
        return 'b' . 'r' . 'e' . 'e' . 'z' . 'e';
    }
}
