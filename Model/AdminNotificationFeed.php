<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Model;

use Magefan\Community\Api\GetModuleVersionInterface;

/**
 * Class AdminNotificationFeed
 * @package Magefan\Community\Model
 */
class AdminNotificationFeed extends \Magento\AdminNotification\Model\Feed
{
    /**
     * @var string
     */
    const MAGEFAN_CACHE_KEY = 'magefan_admin_notifications_lastcheck' ;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $_backendAuthSession;

    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $_moduleList;

    /**
     * @var \Magento\Framework\Module\Manager
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
     * AdminNotificationFeed constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\AdminNotification\Model\InboxFactory $inboxFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     * @param \Magento\Framework\App\ProductMetadataInterface $productMetadata
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param Config $config
     * @param GetModuleVersionInterface $getModuleVersion
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\AdminNotification\Model\InboxFactory $inboxFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\HTTP\Adapter\CurlFactory $curlFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        \Magento\Framework\UrlInterface $urlBuilder,
        Config $config,
        GetModuleVersionInterface $getModuleVersion,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $backendConfig, $inboxFactory, $curlFactory, $deploymentConfig, $productMetadata, $urlBuilder, $resource, $resourceCollection, $data);
        $this->_backendAuthSession  = $backendAuthSession;
        $this->_moduleList = $moduleList;
        $this->_moduleManager = $moduleManager;
        $this->config = $config;
        $this->getModuleVersion = $getModuleVersion;
    }

    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = 'https://mage'.'fan'
                .'.c'.'om/community/notifications'.'/'.'feed/';
        }
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
            return parent::checkUpdate();
        } else {
            return $this;
        }
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
     * @return \SimpleXMLElement
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
            return new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel></channel>
</rss>');
        }

        return parent::getFeedData();
    }
}
