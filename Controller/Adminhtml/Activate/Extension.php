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
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magefan\Community\Model\Section;

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
     * @var DateTime
     */
    private $date;

    /**
     * @param Context $context
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param DateTime $date
     */
    public function __construct(
        Context $context,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        DateTime $date
    )
    {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->date = $date;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        try {
            $activationKey = (string)$this->getRequest()->getParam('activation_key');
            if (!$this->getRequest()->getParam('activation_key')) {
                throw new LocalizedException(__('Activation Key is missing. Please contact Magefan support.'));
            }

            $section = (string)$this->getRequest()->getParam('section');
            if (!$section) {
                throw new LocalizedException(__('Section param is missing. Please contact Magefan support.'));
            }

            $urlInfo = parse_url($this->_url->getCurrentUrl());
            $domain = isset($urlInfo['host']) ? $urlInfo['host'] : '';

            $date = $this->date->gmtDate();
            $key = sha1(date('y-m-d', strtotime($date)) . '_' . $section . '_' . $domain);
            if ($activationKey !== $key) {
                throw new LocalizedException(__('Invalid Activation Key. Please contact Magefan support.'));
            }

            $this->configWriter->save($section . '/g'.'e'.'n'.'e'.'r'.'a'.'l'.'/'.Section::ACTIVE, 1, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
            $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);

            $this->messageManager->addSuccess(__('Thank you. Extension has been activated.'));
            return $this->resultRedirectFactory->create()->setUrl($this->_url->getUrl('adminhtml/system_config/edit', ['section' => $section]));
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            return $this->resultRedirectFactory->create()->setUrl($this->_url->getUrl('adminhtml'));
        }
    }
}
