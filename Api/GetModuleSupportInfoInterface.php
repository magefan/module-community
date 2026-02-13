<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Api;

/**
 *
 * @api
 * @since 2.1.0
 */
interface GetModuleSupportInfoInterface
{
    /**
     * @param array $moduleData
     * @return bool
     */
    public function validSupport(array $moduleData) : bool;
}
