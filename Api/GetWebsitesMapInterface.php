<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Api;

/**
 * Return Websites Map
 *
 * @api
 * @since 2.1.19
 */
interface GetWebsitesMapInterface
{
    /**
     * @api
     * @return array
     */
    public function execute() : array;
}
