<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */
namespace Magefan\Community\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class UpdateWelcomeBlogPost implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * {@inheritdoc}
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();


        $connection = $this->moduleDataSetup->getConnection();
        $tableName = $this->moduleDataSetup->getTable('magefan_blog_post');

        if ($connection->isTableExists($tableName)) {

            $replacesFrom = [
                '\'href="https://magefan.com/magento2-blog-extension"\'',
                '\'href="https://magefan.com/magento2-extensions"\'',
                '\'href="https://magefan.com/magento-2-extensions"\'',
                '\'href="https://magefan.com/blog/magento-2-blog-extension-documentation"\'',
                '\'href="https://magefan.com/blog/add-read-more-tag-to-blog-post-content"\'',

                '\'href="https://github.com/magefan/module-blog"\'',
                '\'href="https://twitter.com/magento2fan"\'',
                '\'href="https://www.facebook.com/magefan/"\''
            ];

            $replaceTo = '\'href="#" rel="nofollow"\'';

            foreach ($replacesFrom as $replaceFrom) {
                $connection->update(
                    $tableName,
                    [
                        'content' => new \Zend_Db_Expr(
                            'REPLACE(content, ' . $replaceFrom . ', ' . $replaceTo . ')'
                        )
                    ],
                    ['content LIKE ?' => '%This is your first post. Edit or delete it%']
                );
            }

            $replacesFrom = [
                '\'Magefan\'',
                '\'Magento Blog\'',
                '\'Magento 2 Blog\''
            ];

            $replaceTo = '\'\'';

            foreach ($replacesFrom as $replaceFrom) {
                $connection->update(
                    $tableName,
                    [
                        'content' => new \Zend_Db_Expr(
                            'REPLACE(content, ' . $replaceFrom . ', ' . $replaceTo . ')'
                        )
                    ],
                    ['content LIKE ?' => '%This is your first post. Edit or delete it%']
                );
            }
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return []; // Specify dependencies if any
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }
}