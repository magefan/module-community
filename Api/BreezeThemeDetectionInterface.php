<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

interface BreezeThemeDetectionInterface
{

    /**
     * @param $storeId
     * @return bool
     */
    public function execute($storeId = null): bool;

}
