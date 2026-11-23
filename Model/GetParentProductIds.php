<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetParentProductIdsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

class GetParentProductIds implements GetParentProductIdsInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var AdapterInterface
     */
    private $connection;

    /**
     * GetParentProductIds constructor.
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Get parent product ids
     *
     * @param array $productIds
     * @return array
     */
    public function execute(array $productIds): array
    {
        $parentProductIds = [];

        /* Fix for configurable, bundle, grouped */
        if ($productIds) {
            $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
            // On Adobe Commerce relation parent_id is row_id, but return entity_id of the parent
            $linkField = $this->connection->tableColumnExists($productTable, 'row_id') ? 'row_id' : 'entity_id';

            $select = $this->connection->select()
                ->distinct()
                ->from(
                    ['main_table' => $this->resourceConnection->getTableName('catalog_product_relation')],
                    []
                )->join(
                    ['e' => $productTable],
                    'e.' . $linkField . ' = main_table.parent_id',
                    ['e.entity_id']
                )
                ->where('main_table.child_id IN (?)', $productIds);

            foreach ($this->connection->fetchCol($select) as $parentId) {
                $parentProductIds[$parentId] = $parentId;
            }
        }
        /* End fix */

        return $parentProductIds;
    }
}
