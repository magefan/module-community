<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Controller\Adminhtml\Activate;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\Type\Config;

class Extension extends \Magento\Backend\App\Action
{
    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var TypeListInterface
     */
    private $cacheTypeList;

    /**
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    )
    {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if (!$this->getRequest()->getParam('activation_key')) {
            throw new NoSuchEntityException(__('Activation key not found.'));
        }
        $urlInfo = parse_url($this->_url->getCurrentUrl());
        $domain = isset($urlInfo['host']) ? $urlInfo['host'] : null;

        $key = sha1(date('y-m-d'). '_' . $this->getRequest()->getParam('section') . '_' . $domain);
        if ($this->getRequest()->getParam('activation_key') !== $key) {
            throw new NoSuchEntityException(__('Invalid activation key provided. Please try again.'));
        }

        if (!$this->getRequest()->getParam('section')) {
            throw new NoSuchEntityException(__('Section not specified.'));
        }
        $section = $this->getRequest()->getParam('section');

        $this->configWriter->save($section . '/g'.'e'.'n'.'e'.'r'.'a'.'l'.'/'.'m'.'f'.'a'.'c'.'t'.'i'.'v'.'e', 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
        $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);

        return $this->resultRedirectFactory->create()->setUrl($this->_url->getUrl('adminhtml/system_config/edit', ['section' => $section]));

    }
}
