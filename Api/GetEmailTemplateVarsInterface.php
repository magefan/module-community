<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

/**
 * Return common email template vars for a Magefan module's transactional emails:
 * product_url, product_name, store_url, store_name
 *
 * @api
 */
interface GetEmailTemplateVarsInterface
{
    /**
     * Get common email template vars
     *
     * @param string $moduleName
     * @param int|null $storeId
     * @return array
     */
    public function execute(string $moduleName, $storeId = null): array;
}
