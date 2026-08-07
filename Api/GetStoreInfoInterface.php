<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

use Magento\Framework\DataObject;

/**
 * Return store info: name (falls back to store view name) and base URL
 *
 * @api
 */
interface GetStoreInfoInterface
{
    /**
     * Get store info
     *
     * @param int|null $storeId
     * @return DataObject
     */
    public function execute($storeId = null);
}
