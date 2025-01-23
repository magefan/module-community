<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Magento\Product;

class CollectionOptimizedForSqlValidator extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    /**
     * Prevent collect of attribute values in  Magento\Rule\Model\Condition\Product\AbstractProduct::collectValidatedAttributes() since it is not used in SQL validator
     * @param string $attribute attribute code
     * @return array
     */
    public function getAllAttributeValues($attribute)
    {
        return [];
    }
}
