<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\ExtensionReview;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Store review popup answers in core_config_data, in one row mfextension/review/data:
 * {
 *   "first_seen": {"<Module>": int},
 *   "reviewed": {"<Module>": int},
 *   "users": {"<admin user id>": {"last_prompt": int, "paused_until": int,
 *       "modules": {"<Module>": {"postponed": int, "updated_at": int}}}}
 * }
 *
 * - first_seen: time the module page was opened in admin for the first time. Nobody is asked about the module
 *   until it has been used for USAGE_DAYS.
 * - reviewed: shared by all admin users. A review of any admin user stops asking anybody about the module forever.
 * - users: answers of each admin user. "Not now" postpones the module only for the admin user who answered,
 *   and any answer pauses only this admin user before being asked about any next module.
 *   A low rating (PAUSE_RATING_MAX or less) pauses asking this admin user about any module for PAUSE_DAYS.
 */
class ReviewStatus
{
    public const XML_PATH = 'mfextension/review/data';

    /**
     * Days the module has to be used before anybody is asked to review it
     */
    public const USAGE_DAYS = 14;

    /**
     * Days to wait before asking the same admin user about any next module after their review or "not now"
     */
    public const NEXT_MODULE_DAYS = 14;

    /**
     * Days to wait before asking the same admin user about the same module again after their 1st, 2nd, 3rd "not now"
     */
    public const REMIND_LATER_DAYS = [30, 60, 90];

    /**
     * Days to wait after each next "not now", when REMIND_LATER_DAYS is over
     */
    public const REMIND_LATER_DAYS_MAX = 180;

    /**
     * Rating this or lower pauses asking the admin user about any module for PAUSE_DAYS
     */
    public const PAUSE_RATING_MAX = 3;

    /**
     * Days to wait before asking the admin user about any next module after a low rating
     */
    public const PAUSE_DAYS = 120;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Json
     */
    private $json;

    /**
     * @var array|null
     */
    private $data;

    /**
     * @param ResourceConnection $resourceConnection
     * @param WriterInterface $configWriter
     * @param Json $json
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        WriterInterface $configWriter,
        Json $json
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configWriter = $configWriter;
        $this->json = $json;
    }

    /**
     * Check whether the review popup can be shown for the module to the admin user
     *
     * @param string $moduleName short module name, e.g. Blog
     * @param int $userId admin user id
     * @return bool
     */
    public function canAsk(string $moduleName, int $userId): bool
    {
        $data = $this->getData();
        if (!empty($data['reviewed'][$moduleName])) {
            return false;
        }

        $now = time();
        $firstSeen = (int)($data['first_seen'][$moduleName] ?? 0);
        if (!$firstSeen) {
            $this->markFirstSeen($moduleName);
            return false;
        }
        if ($firstSeen > $now - self::USAGE_DAYS * 86400) {
            return false;
        }

        $user = $this->getUserData($userId);

        if ($user['paused_until'] > $now) {
            return false;
        }

        if ($user['last_prompt'] && $user['last_prompt'] > $now - self::NEXT_MODULE_DAYS * 86400) {
            return false;
        }

        $module = $user['modules'][$moduleName] ?? [];
        $postponed = (int)($module['postponed'] ?? 0);
        if ($postponed) {
            $days = self::REMIND_LATER_DAYS[$postponed - 1] ?? self::REMIND_LATER_DAYS_MAX;
            if ((int)($module['updated_at'] ?? 0) > $now - $days * 86400) {
                return false;
            }
        }

        return true;
    }

    /**
     * Module was reviewed by any admin user: never ask anybody about it again
     *
     * A low rating also pauses asking this admin user about other modules for PAUSE_DAYS.
     *
     * @param string $moduleName
     * @param int $userId admin user who answered
     * @param int $rating rating of the review, 0 if unknown (e.g. the review was left earlier)
     * @return void
     */
    public function markReviewed(string $moduleName, int $userId, int $rating = 0): void
    {
        $this->update($userId, function (array $data, array $user, int $now) use ($moduleName, $rating): array {
            $data['reviewed'][$moduleName] = $now;
            unset($user['modules'][$moduleName]);
            if ($rating > 0 && $rating <= self::PAUSE_RATING_MAX) {
                $user['paused_until'] = max($user['paused_until'], $now + self::PAUSE_DAYS * 86400);
            }
            return [$data, $user];
        });
    }

    /**
     * Admin user answered "not now" or closed the popup: postpone the module only for this admin user
     *
     * @param string $moduleName
     * @param int $userId admin user who answered
     * @return void
     */
    public function postpone(string $moduleName, int $userId): void
    {
        $this->update($userId, function (array $data, array $user, int $now) use ($moduleName): array {
            $postponed = (int)($user['modules'][$moduleName]['postponed'] ?? 0);
            $user['modules'][$moduleName] = ['postponed' => $postponed + 1, 'updated_at' => $now];
            return [$data, $user];
        });
    }

    /**
     * Remember when the module started to be used, to ask about it only after USAGE_DAYS
     *
     * @param string $moduleName
     * @return void
     */
    private function markFirstSeen(string $moduleName): void
    {
        $this->data = null;
        $data = $this->getData();
        if (!empty($data['first_seen'][$moduleName])) {
            return;
        }

        $data['first_seen'][$moduleName] = time();
        $this->configWriter->save(self::XML_PATH, $this->json->serialize($data));
        $this->data = $data;
    }

    /**
     * Get answers of the admin user
     *
     * @param int $userId
     * @return array
     */
    private function getUserData(int $userId): array
    {
        $user = $this->getData()['users'][$userId] ?? [];
        if (!is_array($user)) {
            $user = [];
        }

        return [
            'last_prompt' => (int)($user['last_prompt'] ?? 0),
            'paused_until' => (int)($user['paused_until'] ?? 0),
            'modules' => isset($user['modules']) && is_array($user['modules']) ? $user['modules'] : [],
        ];
    }

    /**
     * Apply the change and save it together with the time of the admin user's answer
     *
     * Data is re-read right before saving, so an answer of another admin user given meanwhile is not lost.
     *
     * @param int $userId
     * @param callable $change function (array $data, array $user, int $now): array [$data, $user]
     * @return void
     */
    private function update(int $userId, callable $change): void
    {
        $this->data = null;
        $now = time();

        [$data, $user] = $change($this->getData(), $this->getUserData($userId), $now);
        $user['last_prompt'] = $now;
        $data['users'][$userId] = $user;

        $this->configWriter->save(self::XML_PATH, $this->json->serialize($data));
        $this->data = $data;
    }

    /**
     * Read data directly from DB, so config cache does not have to be flushed after each answer
     *
     * @return array
     */
    private function getData(): array
    {
        if (null === $this->data) {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName('core_config_data'), 'value')
                ->where('scope = ?', ScopeConfigInterface::SCOPE_TYPE_DEFAULT)
                ->where('scope_id = ?', 0)
                ->where('path = ?', self::XML_PATH);
            $value = (string)$connection->fetchOne($select);

            $data = [];
            if ($value !== '') {
                try {
                    $data = $this->json->unserialize($value);
                } catch (\InvalidArgumentException $e) {
                    $data = [];
                }
            }
            if (!is_array($data)) {
                $data = [];
            }
            foreach (['first_seen', 'reviewed', 'users'] as $key) {
                if (!isset($data[$key]) || !is_array($data[$key])) {
                    $data[$key] = [];
                }
            }

            $this->data = $data;
        }

        return $this->data;
    }
}
