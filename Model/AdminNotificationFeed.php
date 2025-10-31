<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetModuleVersionInterface;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Config\ConfigOptionsListConstants;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Adapter\Curl;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\Manager;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use SimpleXMLElement;

class AdminNotificationFeed extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var string
     */
    public const MAGEFAN_CACHE_KEY = 'magefan_admin_notifications_lastcheck' ;

    /**
     * @var string
     */
    protected $_feedUrl;

    /**
     * @var mixed
     */
    protected $_inboxFactory;

    /**
     * @var CurlFactory
     *
     */
    protected $curlFactory;

    /**
     * Deployment configuration
     *
     * @var DeploymentConfig
     */
    protected $_deploymentConfig;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var Session
     */
    protected $_backendAuthSession;

    /**
     * @var ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var Manager
     */
    protected $_moduleManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var GetModuleVersionInterface
     */
    private $getModuleVersion;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param Session $backendAuthSession
     * @param ModuleListInterface $moduleList
     * @param Manager $moduleManager
     * @param CurlFactory $curlFactory
     * @param DeploymentConfig $deploymentConfig
     * @param ProductMetadataInterface $productMetadata
     * @param UrlInterface $urlBuilder
     * @param Config $config
     * @param GetModuleVersionInterface $getModuleVersion
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        Session $backendAuthSession,
        ModuleListInterface $moduleList,
        Manager $moduleManager,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        Config $config,
        GetModuleVersionInterface $getModuleVersion,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->curlFactory = $curlFactory;
        $this->_deploymentConfig = $deploymentConfig;
        $this->productMetadata = $productMetadata;
        $this->urlBuilder = $urlBuilder;

        $this->_backendAuthSession  = $backendAuthSession;
        $this->_moduleList = $moduleList;
        $this->_moduleManager = $moduleManager;
        $this->config = $config;
        $this->getModuleVersion = $getModuleVersion;
    }

    /**
     * Init model
     *
     * @return void
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock
     */
    protected function _construct()
    {
    }

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if ($this->_feedUrl === null) {
            $this->_feedUrl = 'https://mage'.'fan'
                .'.c'.'om/community/notifications'.'/'.'feed/';
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $urlInfo = parse_url($this->urlBuilder->getBaseUrl());
        $domain = isset($urlInfo['host']) ? $urlInfo['host'] : '';
        $url = $this->_feedUrl . 'domain/' . urlencode($domain);
        $modulesParams = [];
        foreach ($this->getMagefanModules() as $moduleName => $module) {
            $key = str_replace('Magefan_', '', $moduleName);
            $modulesParams[] = $key . ',' . $this->getModuleVersion->execute($moduleName);
        }
        if (count($modulesParams)) {
            $url .= '/modules/'.base64_encode(implode(';', $modulesParams));
        }

        $receiveNotifications = $this->config->receiveNotifications();
        $notificationsParams = [];
        foreach ($receiveNotifications as $notification => $notificationStatus) {
            $notificationsParams[] = $notification . ',' . $notificationStatus;
        }

        if (count($notificationsParams)) {
            $url .= '/notifications/' . base64_encode(implode(';', $notificationsParams));
        }
        return $url;
    }

    /**
     * Retrieve Magefan modules info
     *
     * @return $this
     */
    protected function getMagefanModules()
    {
        $modules = [];
        foreach ($this->_moduleList->getAll() as $moduleName => $module) {
            if (strpos($moduleName, 'Magefan_') !== false && $this->_moduleManager->isEnabled($moduleName)) {
                $modules[$moduleName] = $module;
            }
        }
        return $modules;
    }

    /**
     * Check feed for modification
     *
     * @return $this
     */
    public function checkUpdate()
    {
        $session = $this->_backendAuthSession;
        $time = time();
        $frequency = $this->getFrequency();
        if (($frequency + $session->getMfNoticeLastUpdate() > $time)
            || ($frequency + $this->getLastUpdate() > $time)
        ) {
            return $this;
        }
        $session->setMfNoticeLastUpdate($time);

        if ($this->_moduleManager->isEnabled('Magento_AdminNotification')) {
            return $this->parentCheckUpdate();
        } else {
            return $this;
        }
    }

    /**
     * Check feed for modification
     *
     * @return $this
     */
    protected function parentCheckUpdate()
    {
        if ($this->getFrequency() + $this->getLastUpdate() > time()) {
            return $this;
        }

        $feedData = [];

        $feedXml = $this->getFeedData();

        $installDate = strtotime($this->_deploymentConfig->get(ConfigOptionsListConstants::CONFIG_PATH_INSTALL_DATE));

        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {
            foreach ($feedXml->channel->item as $item) {
                $itemPublicationDate = strtotime((string)$item->pubDate);
                if ($installDate <= $itemPublicationDate) {
                    $feedData[] = [
                        'severity' => (int)$item->severity,
                        'date_added' => date('Y-m-d H:i:s', $itemPublicationDate),
                        'title' => $this->escapeString($item->title),
                        'description' => $this->escapeString($item->description),
                        'url' => $this->escapeString($item->link),
                    ];
                }
            }

            if ($feedData) {
                $this->getInboxFactory()->create()->parse(array_reverse($feedData));
            }
        }
        $this->setLastUpdate();

        return $this;
    }

    /**
     * Retrieve update аrequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return 86400 * 2;
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return $this->_cacheManager->load(self::MAGEFAN_CACHE_KEY);
    }

    /**
     * Set last update time (now)
     *
     * @return $this
     */
    public function setLastUpdate()
    {
        $this->_cacheManager->save(time(), self::MAGEFAN_CACHE_KEY);
        return $this;
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement
     */
    public function getFeedData()
    {
        $getNotification = false;
        foreach ($this->config->receiveNotifications() as $key => $value) {
            if ($value) {
                $getNotification = true;
                break;
            }
        }

        if (!$getNotification) {
            return new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel></channel>
</rss>');
        }

        return $this->parentGetFeedData();
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement
     */
    protected function parentGetFeedData()
    {
        /** @var Curl $curl */
        $curl = $this->curlFactory->create();
        $curl->setOptions(
            [
                CURLOPT_TIMEOUT => 2,
                CURLOPT_USERAGENT => $this->productMetadata->getName()
                    . '/' . $this->productMetadata->getVersion()
                    . ' (' . $this->productMetadata->getEdition() . ')',
                CURLOPT_REFERER => $this->urlBuilder->getUrl('*/*/*')
            ]
        );
        $curl->write('GET', $this->getFeedUrl(), '1.0');
        $data = $curl->read();
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1] ?? '');
        $curl->close();

        try {
            $xml = new SimpleXMLElement($data);
        } catch (\Exception $e) {
            return false;
        }

        return $xml;
    }
    /**
     * Retrieve feed as XML element
     *
     * @return SimpleXMLElement
     */
    public function getFeedXml()
    {
        try {
            $data = $this->getFeedData();
            $xml = new SimpleXMLElement($data);
        } catch (\Exception $e) {
            $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?>');
        }

        return $xml;
    }

    /**
     * Converts incoming data to string format and escapes special characters.
     *
     * @param SimpleXMLElement $data
     * @return string
     */
    private function escapeString(SimpleXMLElement $data)
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        return htmlspecialchars((string)$data);
    }

    /**
     * Get inbox factory
     *
     * @return mixed
     */
    private function getInboxFactory()
    {
        if (null === $this->_inboxFactory) {
            // phpcs:disable
            $this->_inboxFactory = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Magento\AdminNotification\Model\InboxFactory::class);
            // phpcs:enable
        }

        return $this->_inboxFactory;
    }
}
