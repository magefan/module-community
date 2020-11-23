<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * Receive Notifications
     */
    const XML_PATH_RECEIVE_PRODUCT_UPDATES = 'extension/notification/update';
    const XML_PATH_RECEIVE_SPECIAL_OFFERS = 'extension/notification/offer';
    const XML_PATH_RECEIVE_NEWS = 'extension/notification/news';
    const XML_PATH_RECEIVE_TIPS_AND_TRICKS = 'extension/notification/tip_trick';
    const XML_PATH_RECEIVE_GENERAL_INFORMATION = 'extension/notification/general';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Receive Product Updates
     *
     * @param null $storeId
     * @return string
     */
    public function receiveProductUpdates($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_RECEIVE_PRODUCT_UPDATES,
            $storeId
        );
    }

    /**
     * Receive Special Offers
     *
     * @param null $storeId
     * @return string
     */
    public function receiveSpecialOffers($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_RECEIVE_SPECIAL_OFFERS,
            $storeId
        );
    }

    /**
     * Receive News
     *
     * @param null $storeId
     * @return string
     */
    public function receiveNews($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_RECEIVE_NEWS,
            $storeId
        );
    }

    /**
     * Receive Tips & Tricks
     *
     * @param null $storeId
     * @return string
     */
    public function receiveTipsAndTricks($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_RECEIVE_TIPS_AND_TRICKS,
            $storeId
        );
    }

    /**
     * Receive General Information
     *
     * @param null $storeId
     * @return string
     */
    public function receiveGeneralInformation($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_RECEIVE_GENERAL_INFORMATION,
            $storeId
        );
    }

    /**
     * Receive Notifications
     *
     * @param null $storeId
     * @return array
     */
    public function receiveNotifications($storeId = null)
    {
        return [
            'update' => $this->receiveProductUpdates(),
            'offer' => $this->receiveSpecialOffers(),
            'news' => $this->receiveNews(),
            'tip_trick' => $this->receiveTipsAndTricks(),
            'general' => $this->receiveGeneralInformation()
        ];
    }

    /**
     * Retrieve store config value
     *
     * @param string $path
     * @param null $storeId
     * @return mixed
     */
    public function getConfig($path, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
