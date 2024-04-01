<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetParentProductIdsInterface;
use Magento\Framework\App\ResourceConnection;


class GetParentProductIds implements GetParentProductIdsInterface
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
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
     * @param array $productIds
     * @return array
     */
    public function execute(array $productIds): array
    {
        $parentProductIds = [];

        /* Fix for configurable, bundle, grouped */
        if ($productIds) {
            $productTable = $this->resourceConnection->getTableName('catalog_product_entity');
            $entityIdColumn = $this->connection->tableColumnExists($productTable, 'row_id') ? 'row_id' : 'entity_id';

            $select = $this->connection->select()
                ->from(
                    ['main_table' => $this->resourceConnection->getTableName('catalog_product_relation')],
                    []
                )->join(
                    ['e' => $productTable],
                    'e.' . $entityIdColumn . ' = main_table.parent_id',
                    ['e.' .$entityIdColumn]
                )
                ->where('main_table.child_id IN (?)', $productIds)
                ->where('e.entity_id IS NOT NULL');

            foreach ($this->connection->fetchAll($select) as $product) {
                $parentProductIds[$product[$entityIdColumn]] = $product[$entityIdColumn];
            }
        }
        /* End fix */

        return $parentProductIds;
    }
}
