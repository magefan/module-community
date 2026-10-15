<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

/**
 * Return category by product
 *
 * @api
 * @since 2.1.10
 */
interface GetCategoryByProductInterface
{
    /**
     * Get category by product
     *
     * @param mixed $product
     * @param mixed $storeId
     * @returnmixed
     */
    public function execute($product, $storeId = null);
}
