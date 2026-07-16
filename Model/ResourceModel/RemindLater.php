<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class RemindLater
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Fetch a single remind-later row for the given user / module / event.
     *
     * @param int $userId
     * @param string $moduleName
     * @param string $event
     * @return array|false
     */
    public function getRow(int $userId, string $moduleName, string $event)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('mf_message_remind_later');

        $select = $connection->select()
            ->from($table, ['id', 'created_at'])
            ->where('admin_user_id = ?', $userId)
            ->where('module_name = ?', $moduleName)
            ->where('event = ?', $event)
            ->limit(1);

        return $connection->fetchRow($select);
    }

    /**
     * Insert a new remind-later row.
     *
     * @param int $userId
     * @param string $moduleName
     * @param string $event
     * @return void
     */
    public function insert(int $userId, string $moduleName, string $event): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('mf_message_remind_later');

        $connection->insert($table, [
            'admin_user_id' => $userId,
            'module_name'   => $moduleName,
            'event'         => $event,
        ]);
    }

    /**
     * Insert or update a remind-later row, refreshing created_at on duplicate.
     *
     * @param int $userId
     * @param string $moduleName
     * @param string $event
     * @return void
     */
    public function upsert(int $userId, string $moduleName, string $event): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('mf_message_remind_later');

        $connection->insertOnDuplicate(
            $table,
            ['admin_user_id' => $userId, 'module_name' => $moduleName, 'event' => $event],
            ['created_at']
        );
    }
}
