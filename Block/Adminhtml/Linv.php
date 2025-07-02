<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ResourceConnection;
use Magefan\Community\Model\SectionFactory;

class Linv extends Template
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var SectionFactory
     */
    private $sectionFactory;

    /**
     * @param SectionFactory $sectionFactory
     * @param Template\Context $context
     * @param ResourceConnection $resource
     * @param array $data
     */
    public function __construct(
        SectionFactory $sectionFactory,
        Template\Context $context,
        ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->sectionFactory = $sectionFactory;
        $this->resource = $resource;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        $path = '/g'.'en'.'er'.'al'.'/l'.'in'.'v';
        $condition = '=';
        $value = 1;

        return $this->fetchConfigData($path, $condition, $value);
    }

    /**
     * @return array
     */
    public function getMessages() {
        $path = '/g'.'en'.'er'.'al'.'/l'.'in'.'v'.'me'.'ss'.'ag'.'e';
        $condition = '!=';
        $value = '';

        return $this->fetchConfigData($path, $condition, $value);
    }

    /**
     * @param string $path
     * @param string $condition
     * @param $value
     * @return array
     */
    private function fetchConfigData(string $path, string $condition, $value) {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('core_config_data');

        $select = $connection->select()
            ->from([$table])
            ->where( 'path LIKE ?', '%' . $path )
            ->where('value ' . $condition . ' ?', $value);
        $items = $connection->fetchAll($select);
        $result = [];

        foreach ($items as $config) {
            $configPath = explode('/', $config['path']);
            $moduleName = $configPath[0];
            $section = $this->sectionFactory->create([
                'name' => $moduleName
            ]);
            $module = $section->getModule(true);
            if ($module && !$section->isEnabled()) {
                $result[] = $module;
            }
        }
        return $result;
    }
}
