<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

use Magento\Framework\DataObject;

/**
 * Return module info
 *
 * @api
 * @since 2.1.0
 */
interface GetModuleInfoInterface
{
    /**
     * Get data by module
     *
     * @param string $moduleName
     * @return array|DataObject|mixed
     */
    public function execute($moduleName = null);
}
