<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

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
     * @param $moduleName
     * @return array|DataObject|mixed
     */
    public function execute($moduleName = null);
}
