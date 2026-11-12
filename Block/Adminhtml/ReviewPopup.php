<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Block\Adminhtml;

use Magento\Directory\Helper\Data as DirectoryHelper;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Module\Manager as ModuleManager;
use Magefan\Community\Model\Config;
use Magefan\Community\Model\ExtensionReview\GetReviewUrls;
use Magefan\Community\Model\ExtensionReview\ReviewStatus;
use Magento\Framework\Serialize\Serializer\Json;

class ReviewPopup extends \Magento\Backend\Block\Template
{
    /**
     * @var array|null
     */
    private $moduleInfo = null;

    /**
     * @var string|null
     */
    private $resolvedModuleName = null;

    /**
     * @var \Magefan\Community\Model\GetModuleInfo
     */
    private $getModuleInfo;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    private $authSession;

    /**
     * @var ReviewStatus
     */
    private $reviewStatus;

    /**
     * @var FormKey
     */
    private $mfFormKey;

    /**
     * @var ModuleManager
     */
    private $mfModuleManager;

    /**
     * @var GetReviewUrls
     */
    private $getReviewUrls;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magefan\Community\Model\GetModuleInfo $getModuleInfo
     * @param Config $config
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param ReviewStatus $reviewStatus
     * @param FormKey $formKey
     * @param ModuleManager $moduleManager
     * @param GetReviewUrls $getReviewUrls
     * @param Json $json
     * @param array $data
     * @param JsonHelper|null $jsonHelper
     * @param DirectoryHelper|null $directoryHelper
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magefan\Community\Model\GetModuleInfo $getModuleInfo,
        Config $config,
        \Magento\Backend\Model\Auth\Session $authSession,
        ReviewStatus $reviewStatus,
        FormKey $formKey,
        ModuleManager $moduleManager,
        GetReviewUrls $getReviewUrls,
        Json $json,
        array $data = [],
        ?JsonHelper $jsonHelper = null,
        ?DirectoryHelper $directoryHelper = null
    ) {
        $this->getModuleInfo = $getModuleInfo;
        $this->config = $config;
        $this->authSession = $authSession;
        $this->reviewStatus = $reviewStatus;
        $this->mfFormKey = $formKey;
        $this->mfModuleManager = $moduleManager;
        $this->getReviewUrls = $getReviewUrls;
        $this->json = $json;
        parent::__construct($context, $data, $jsonHelper, $directoryHelper);
    }

    /**
     * Return the full Magefan module name to ask a review for on the current admin page.
     *
     * Modules of the page come from the Info block (Magefan\Community\Block\Adminhtml\Info::getModuleNames).
     * If several extensions enhance the page, the first one that can be asked about is used.
     *
     * @return string|null
     */
    public function getModuleName()
    {
        if ($this->resolvedModuleName === null) {
            $this->resolvedModuleName = $this->resolveModuleName() ?: '';
        }

        return $this->resolvedModuleName ?: null;
    }

    /**
     * Resolve module name, see getModuleName()
     *
     * @return string|null
     */
    private function resolveModuleName(): ?string
    {
        $infoBlock = $this->getLayout()->getBlock('magefan.module.info');
        if (!$infoBlock instanceof Info) {
            return null;
        }

        foreach ($infoBlock->getModuleNames() as $module) {
            $shortName = explode('_', $module)[1] ?? '';
            if ($shortName
                && $this->mfModuleManager->isEnabled($module)
                && $this->reviewStatus->canAsk($shortName, $this->getAdminUserId())
                && $this->getReviewUrls->execute($module)
            ) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Return the short module name without the vendor prefix.
     *
     * @return string
     */
    public function getModuleShortName(): string
    {
        $moduleName = $this->getModuleName();
        if ($moduleName && strpos($moduleName, '_') !== false) {
            return explode('_', $moduleName)[1];
        }
        return (string)$moduleName;
    }

    /**
     * Return the extension icon URL from Magefan, empty string if there is no valid https URL
     *
     * @return string
     */
    public function getProductImage(): string
    {
        $info = $this->getModuleInfo();
        $url = !empty($info['product_image']) ? (string)$info['product_image'] : '';

        return preg_match('#^https://#i', $url) ? $url : '';
    }

    /**
     * Return the extension page URL on Magefan, empty string if there is no valid https URL
     *
     * @return string
     */
    public function getProductUrl(): string
    {
        $info = $this->getModuleInfo();
        $url = !empty($info['product_url']) ? (string)$info['product_url'] : '';
        if (!preg_match('#^https://#i', $url)) {
            return '';
        }

        return $url . (strpos($url, '?') === false ? '?' : '&')
            . 'utm_source=admin&utm_medium=popup&utm_campaign=review-form';
    }

    /**
     * Return the product display name for the current module.
     *
     * @return string
     */
    public function getProductName(): string
    {
        $info = $this->getModuleInfo();
        if (!empty($info['product_name'])) {
            return str_replace('Magento 2', 'Magefan', $info['product_name']);
        }
        return '';
    }

    /**
     * Return the current admin form key for CSRF protection.
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->mfFormKey->getFormKey();
    }

    /**
     * Return cached module info from the remote source.
     *
     * @return array|\Magento\Framework\DataObject|mixed
     */
    private function getModuleInfo()
    {
        if ($this->moduleInfo === null) {
            $this->moduleInfo = $this->getModuleInfo->execute($this->getModuleName());
        }
        return $this->moduleInfo;
    }

    /**
     * Check whether the review popup can be displayed for the current user and module.
     *
     * @return bool
     */
    private function canDisplay(): bool
    {
        if (!$this->config->isReviewRequestsEnabled()) {
            return false;
        }

        $moduleName = $this->getModuleShortName();
        if (!$moduleName) {
            return false;
        }

        $user = $this->authSession->getUser();
        if (!$user) {
            return false;
        }

        if (!$this->reviewStatus->canAsk($moduleName, (int)$user->getId())) {
            return false;
        }

        return $this->getReviewUrls->execute($moduleName)
            && $this->getProductName();
    }

    /**
     * Current admin user id
     *
     * @return int
     */
    private function getAdminUserId(): int
    {
        $user = $this->authSession->getUser();
        return $user ? (int)$user->getId() : 0;
    }

    /**
     * Get current admin user data to prefill the review form
     *
     * @param string $field
     * @return string
     */
    public function getAdminUserData(string $field): string
    {
        $user = $this->authSession->getUser();
        return $user ? (string)$user->getData($field) : '';
    }

    /**
     * Config of the Magefan_Community/js/review-popup component for x-magento-init
     *
     * @return string JSON
     */
    public function getJsConfig(): string
    {
        $productName = $this->getProductName();

        return $this->json->serialize([
            'Magefan_Community/js/review-popup' => [
                'requestUrl' => $this->getUrl('mfcommunity/review/index'),
                'formKey' => $this->getFormKey(),
                'module' => $this->getModuleShortName(),
                'showDelay' => 3000,
                'texts' => [
                    'formTitle' => (string)__('Leave a Review for %1', $productName),
                    'submit' => (string)__('Submit'),
                    'thankYou' => (string)__('Thank you!'),
                    'submitted' => (string)__('Your review has been submitted successfully.'),
                    'alreadyReviewed' => (string)__('You have already left a review for this extension.'),
                    'error' => (string)__('Error'),
                    'errorMessage' => (string)__(
                        'An error occurred while submitting your review. Please try again later.'
                    ),
                ],
            ],
        ]);
    }

    /**
     * Render block HTML or return empty string when popup should not be shown.
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->canDisplay()) {
            return '';
        }
        return parent::_toHtml();
    }
}
