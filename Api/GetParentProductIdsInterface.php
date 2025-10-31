<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

/**
 * Return parent ids by child ids
 *
 * @api
 * @since 2.1.19
 */
interface GetParentProductIdsInterface
{
    /**
     * Get parent product ids
     *
     * @api
     * @param array $productIds
     * @return array
     */
    public function execute(array $productIds) : array;
}
