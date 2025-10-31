<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    /**
     * Receive Notifications
     */
    public const XML_PATH_RECEIVE_PRODUCT_UPDATES = 'mfextension/notification/update';
    public const XML_PATH_RECEIVE_SPECIAL_OFFERS = 'mfextension/notification/offer';
    public const XML_PATH_RECEIVE_NEWS = 'mfextension/notification/news';
    public const XML_PATH_RECEIVE_TIPS_AND_TRICKS = 'mfextension/notification/tip_trick';
    public const XML_PATH_RECEIVE_GENERAL_INFORMATION = 'mfextension/notification/general';

    /**
     * Display Menu
     */
    public const XML_PATH_MENU_ENABLED = 'mfextension/menu/display';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config constructor.
     *
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
     * @param mixed $storeId
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
     * @param mixed $storeId
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
     * @param mixed $storeId
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
     * @param mixed $storeId
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
     * @param mixed $storeId
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
     * @param mixed $storeId
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
     * Display Menu
     *
     * @param mixed $storeId
     * @return string
     */
    public function menuEnabled($storeId = null)
    {
        return $this->getConfig(
            self::XML_PATH_MENU_ENABLED,
            $storeId
        );
    }

    /**
     * Retrieve store config value
     *
     * @param string $path
     * @param mixed $storeId
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
