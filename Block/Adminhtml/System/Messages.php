<?php

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml\System;

use Magefan\Community\Api\GetModuleInfoInterface;
use Magefan\Community\Api\GetModuleSupportInfoInterface;
use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\Community\Model\Config;
use Magefan\Community\Model\SectionFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Config\DomFactory;
use Magento\Framework\Module\Dir\Reader as ModuleDirReader;
use Magento\Framework\App\Route\Config as RouteConfig;

class Messages extends \Magento\Backend\Block\Template
{
    protected $latestVersion = null;
    protected $currentVersion = null;
    protected $extensionName = null;
    protected $allowedMasage = null;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RouteConfig
     */
    private $routeConfig;

    /**
     * @var ModuleDirReader
     */
    private $moduleDirReader;

    /**
     * @var DomFactory
     */
    private $domFactory;

    /**
     * @var AuthSession
     */
    private $authSession;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SectionFactory
     */
    private $sectionFactory;

    /**
     * @var GetModuleInfoInterface|mixed
     */
    private $getModuleInfo;

    /**
     * @var GetModuleVersionInterface|mixed
     */
    private $getModuleVersion;

    /**
     * @var \Magefan\Community\Model\MessagePool
     */
    private $messagePool;

    /**
     * @var GetModuleSupportInfoInterface|mixed
     */
    private $getModuleSupportInfo;

    /**
     * @param Context $context
     * @param Config $config
     * @param RouteConfig $routeConfig
     * @param ModuleDirReader $moduleDirReader
     * @param DomFactory $domFactory
     * @param AuthSession $authSession
     * @param ResourceConnection $resourceConnection
     * @param SectionFactory $sectionFactory
     * @param \Magefan\Community\Model\MessagePool $messagePool
     * @param array $data
     * @param GetModuleInfoInterface|null $getModuleInfo
     * @param GetModuleVersionInterface|null $getModuleVersion
     * @param GetModuleSupportInfoInterface|null $getModuleSupportInfo
     */
    public function __construct(
        Context $context,
        Config $config,
        RouteConfig $routeConfig,
        ModuleDirReader $moduleDirReader,
        DomFactory $domFactory,
        AuthSession $authSession,
        ResourceConnection $resourceConnection,
        SectionFactory $sectionFactory,
        \Magefan\Community\Model\MessagePool $messagePool,
        array $data = [],
        GetModuleInfoInterface $getModuleInfo = null,
        GetModuleVersionInterface $getModuleVersion = null,
        GetModuleSupportInfoInterface $getModuleSupportInfo = null
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->routeConfig = $routeConfig;
        $this->moduleDirReader = $moduleDirReader;
        $this->domFactory = $domFactory;
        $this->authSession = $authSession;
        $this->resourceConnection = $resourceConnection;
        $this->sectionFactory = $sectionFactory;
        $this->messagePool = $messagePool;
        $this->getModuleInfo = $getModuleInfo ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(GetModuleInfoInterface::class);
        $this->getModuleVersion = $getModuleVersion ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Community\Api\GetModuleVersionInterface::class
        );
        $this->getModuleSupportInfo = $getModuleSupportInfo ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            GetModuleSupportInfoInterface::class
        );
    }

    /**
     * @return array|string[]
     */
    public function getNotificationData()
    {
        $data = $this->messagePool->getAll($this->getRequest()->getFullActionName());
        if ($data) {
            return $data;
        }
        return [$this->getFormattedModuleName() => 'all'];
    }
    /**
     * @return bool
     */
    public function isEnabled() {
        foreach ($this->_storeManager->getStores() as $store) {
            $configPath = $this->getConfigSection() . '/' . 'g' . 'e' . 'n' . 'e' . 'r' . 'a' . 'l' . '/' . 'e' . 'n' . 'a' . 'b' . 'l' . 'e' . 'd';
            if ($this->config->getConfig($configPath, (int)$store->getId())) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array|false|\Magento\Framework\DataObject|mixed
     */
    public function getModuleInfo() {
        return $this->getFormattedModuleName() ? $this->getModuleInfo->execute($this->getFormattedModuleName()) : false;
    }

    /**
     * @return string
     */
    private function getCurrentVersion() {
        if (!$this->currentVersion) {
            $moduleName = $this->getFormattedModuleName();
            $currentVersion = $this->getModuleVersion->execute($moduleName);

            foreach (['Extra', 'Plus'] as $_plan) {
                if ($_currentVersion = $this->getModuleVersion->execute($moduleName . $_plan)) {
                    $currentVersion = $_currentVersion;
                    break;
                }
            }

            $this->currentVersion = $currentVersion;
        }
        return $this->currentVersion;
    }

    /**
     * @return string
     */
    public function getLatestVersion() {
        if (!$this->latestVersion) {
            try {
                $moduleInfo = $this->getModuleInfo();
                $this->latestVersion = $moduleInfo ? $moduleInfo->getVersion() : '';
            } catch (\Exception $e) {
                $this->latestVersion = '';
            }
        }
        return $this->latestVersion;
    }

    /**
     * @return bool|int
     */
    public function needToUpdate() {
        return version_compare($this->getCurrentVersion(), $this->getLatestVersion(), '<');
    }

    /**
     * @param $name
     * @return void
     */
    public function setExtensionName($name = null) {
        $this->allowedMasage = null;
        $this->latestVersion = null;
        $this->currentVersion = null;
        if ($name) {
            $this->extensionName = $name;
            return;
        }
        $frontModule = $this->routeConfig->getModulesByFrontName($this->getRequest()->getModuleName());

        if (!empty($frontModule[0]) && strpos($frontModule[0], 'Magefan_') !== false) {
            $this->extensionName = $frontModule[0];
        } /*else {
            $sectionName = (string)$this->getRequest()->getParam('section');
            $section = $this->sectionFactory->create(['name' => $sectionName]);
            $this->extensionName = $section->getModuleName();
        }*/
    }

    /**
     * @return mixed|null
     */
    private function getExtensionName() {
        if (!$this->extensionName) {
            $this->setExtensionName();
        }
        return $this->extensionName;
    }

    /**
     * @return mixed|string|null
     */
    private function getFormattedModuleName() {
        $moduleName = $this->getExtensionName() ?? '';

        $moduleName = str_starts_with($moduleName, 'Magefan_') ? $moduleName : 'Magefan_' . $moduleName;
        return str_replace(['Extra', 'Plus'], '', $moduleName);
    }

    /**
     * @return null
     */
    public function getConfigSection() {

        if ($this->getRequest()->getParam('section')) {
            return $this->getRequest()->getParam('section');
        }

        $moduleName = $this->getFormattedModuleName();
        if (!$moduleName) {
            return null;
        }

        $configPath = $this->moduleDirReader->getModuleDir(
                \Magento\Framework\Module\Dir::MODULE_ETC_DIR,
                $moduleName
            ) . '/ad' . 'min' . 'ht' . 'ml/' . 'sy' . 'st' . 'em' . '.x' . 'ml';

        if (!file_exists($configPath)) {
            return null;
        }

        $dom = $this->domFactory->createDom(['xml' => file_get_contents($configPath)]);
        $xpath = new \DOMXPath($dom->getDom());

        foreach ($xpath->query('/config/system/section') as $sectionNode) {
            return $sectionNode->getAttribute('id');
        }

        return null;
    }

    /**
     * @return bool
     */
    public function canUpgradePlan() {
        $maxPlan = $this->getModuleInfo()->getMaxPlan();
        $extensionName = $this->getFormattedModuleName();
        return $maxPlan && !$this->getModuleVersion->execute($extensionName. ucfirst($maxPlan));
    }

    /**
     * @param string $event
     * @param $allowedMessages
     * @return bool
     */
    public function allowShowMessage(string $event, string $allowedMessages) {

//        $priority = ['enabled', 'update', 'support', 'upgrade'];

        if ($this->allowedMasage ) {
            return false;
        }

        if ($allowedMessages !== 'all') {
            $allowedEvents = array_map('trim', explode(',', $allowedMessages));
            if (!in_array($event, $allowedEvents)) {
                return false;
            }
        }

        $adminUser = $this->authSession->getUser();
        if (!$adminUser) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('mf_message_remind_later');

        $select = $connection->select()
            ->from($tableName)
            ->where('user_id = ?', $adminUser->getId())
            ->where('event = ?', $event)
            ->where('module_name = ?', str_replace(['Magento 2 ', ' Extension'], '', $this->getModuleInfo()->getProductName()))
            ->where('created_at >= ?', (new \DateTime('-1 day'))->format('Y-m-d H:i:s'))
            ->limit(1);

        if ($connection->fetchOne($select)) {
            return false;
        }

//        if (array_search($event, $priority) >= 0) {
            $this->allowedMasage = $event;
//        }
        return true;
    }


    /**
     * @return bool
     */
    public function getSupportExpired()
    {
        if ($this->getFormattedModuleName() && $key = $this->config->getConfig($this->getConfigSection() . '/' . 'g' . 'e' . 'n' . 'e' . 'r' . 'a' . 'l' . '/' . 'k' . 'e' . 'y')) {
            return !$this->getModuleSupportInfo->validSupport([
                'key' => $key,
                'name' => explode('_', $this->getFormattedModuleName())[1]
            ]);
        }
        return false;
    }
}
