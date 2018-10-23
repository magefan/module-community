<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Block\Adminhtml\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use \Magento\Framework\AuthorizationInterface;

/**
 * Class GenericButton
 */
class GenericButton
{
    const ADMIN_RESOURCE = '';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var AuthorizationInterface
     */
    protected  $authorization;

    /**
     * GenericButton constructor.
     * @param Context $context
     * @param AuthorizationInterface $authorization
     */
    public function __construct(
        Context $context,
        AuthorizationInterface   $authorization
    ) {
        $this->context = $context;
        $this->authorization = $authorization;
    }

    /**
     * Return CMS block ID
     *
     * @return int|null
     */
    public function getObjectId()
    {
        return $this->context->getRequest()->getParam('id');
    }

    /**
     * Generate url by route and parameters
     *
     * @param   string $route
     * @param   array $params
     * @return  string
     */
    public function getUrl($route = '', $params = [])
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }

    public function isAllowed() {
        if (!self::ADMIN_RESOURCE) {
            return true;
        }
        return $this->authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
