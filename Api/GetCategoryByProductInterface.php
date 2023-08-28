<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Api;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product;

/**
 * Return category by product
 *
 * @api
 * @since 2.1.10
 */
interface GetCategoryByProductInterface
{
    /**
     * Get product category
     *
     * @param Product $product
     * @param int $storeId
     * @return CategoryInterface|null
     * @api
     */
    public function execute(Product $product, int $storeId = null): ?CategoryInterface;
}
