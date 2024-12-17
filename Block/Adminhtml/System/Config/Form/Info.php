<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\Community\Api\SecureHtmlRendererInterface;

/**
 * Admin Magefan configurations information block
 */
class Info extends \Magento\Config\Block\System\Config\Form\Field
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var GetModuleVersionInterface
     */
    protected $getModuleVersion;

    /**
     * @var SecureHtmlRendererInterface
     */
    protected $mfSecureRenderer;

    /**
     * @var ExtensionsInfo
     */
    protected $extensionsInfo;

    /**
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param ExtensionsInfo $extensionsInfo
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param GetModuleVersionInterface|null $getModuleVersion
     * @param SecureHtmlRendererInterface|null $mfSecureRenderer
     */
    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magefan\Community\Block\Adminhtml\System\Config\Form\ExtensionsInfo $extensionsInfo,
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        GetModuleVersionInterface $getModuleVersion = null,
        SecureHtmlRendererInterface $mfSecureRenderer = null
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->extensionsInfo = $extensionsInfo;
        $this->getModuleVersion = $getModuleVersion ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Community\Api\GetModuleVersionInterface::class
        );
        $this->mfSecureRenderer = $mfSecureRenderer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SecureHtmlRendererInterface::class);
    }

    /**
     * Return info block html
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $useUrl = \Magefan\Community\Model\UrlChecker::showUrl($this->getUrl());
        $version = $this->getModuleVersion->execute($this->getModuleName());

        if (!$this->getModuleImage()) {
            $html = '<div style="padding:10px;background-color:#f8f8f8;border:1px solid #ddd;margin-bottom:7px;">
            ' . $this->escapeHtml($this->getModuleTitle()) . ' v' . $this->escapeHtml($version) . ' was developed by ';
            if ($useUrl) {
                $html .= '<a href="' . $this->escapeHtml($this->getModuleUrl()) . '" target="_blank">Magefan</a>';
            } else {
                $html .= '<strong>Magefan</strong>';
            }
            $html .= '.</div>';

            return $html;
        }

        $titleAndVersion = $this->escapeHtml($this->getModuleTitle()) . ' v' . $this->escapeHtml($version);
        $newVersionAvailable = version_compare($this->getModuleInfo()['version'], $this->getModuleVersion->execute($this->getModuleName())) > 0;

        $html = '<div class="section-info">
    <div class="col-info">
        <div class="product-icon">';
        if ($this->getModuleImage()) {
            $html .= '<img src="' .  $this->escapeHtml($this->getModuleImage()) . '" alt=""/>';
        }
        $html .= '</div>
        <div class="product-info-wrapper">
            <div class="row-1">
                <div class="block-title">' . $titleAndVersion . '</div>
            </div>
            <div class="row-2">
                <span class="block-dev">developed by ';

                    if ($useUrl) {
                        $html .= '<a href="' . $this->escapeHtml($this->getModuleUrl()) . '" target="_blank">Magefan</a>';
                    } else {
                        $html .= '<strong>Magefan</strong>';
                    }

            $html .= '</span>
                <span class="block-dot">&middot;</span>
                <span class="block-guide">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                        <path d="M6.41797 16.4987H15.5846C16.314 16.4987 17.0135 16.209 17.5292 15.6932C18.0449 15.1775 18.3346 14.478 18.3346 13.7487V4.58203C18.3346 3.85269 18.0449 3.15321 17.5292 2.63749C17.0135 2.12176 16.314 1.83203 15.5846 1.83203H6.41797C5.68862 1.83203 4.98915 2.12176 4.47343 2.63749C3.9577 3.15321 3.66797 3.85269 3.66797 4.58203V17.4154C3.66797 18.1447 3.9577 18.8442 4.47343 19.3599C4.98915 19.8756 5.68862 20.1654 6.41797 20.1654H17.418C17.6611 20.1654 17.8942 20.0688 18.0662 19.8969C18.2381 19.725 18.3346 19.4918 18.3346 19.2487C18.3346 19.0056 18.2381 18.7724 18.0662 18.6005C17.8942 18.4286 17.6611 18.332 17.418 18.332H6.41797C6.17485 18.332 5.9417 18.2355 5.76979 18.0635C5.59788 17.8916 5.5013 17.6585 5.5013 17.4154C5.5013 17.1723 5.59788 16.9391 5.76979 16.7672C5.9417 16.5953 6.17485 16.4987 6.41797 16.4987ZM5.5013 4.58203C5.5013 4.33892 5.59788 4.10576 5.76979 3.93385C5.9417 3.76194 6.17485 3.66536 6.41797 3.66536H15.5846C15.8278 3.66536 16.0609 3.76194 16.2328 3.93385C16.4047 4.10576 16.5013 4.33892 16.5013 4.58203V13.7487C16.5013 13.9918 16.4047 14.225 16.2328 14.3969C16.0609 14.5688 15.8278 14.6654 15.5846 14.6654H6.41797C6.10573 14.6652 5.79574 14.7182 5.5013 14.8221V4.58203Z" fill="#DA5D28"/>
                        <path d="M10.9987 12.8333C11.2418 12.8333 11.475 12.7368 11.6469 12.5648C11.8188 12.3929 11.9154 12.1598 11.9154 11.9167V9.16667C11.9154 8.92355 11.8188 8.69039 11.6469 8.51849C11.475 8.34658 11.2418 8.25 10.9987 8.25C10.7556 8.25 10.5224 8.34658 10.3505 8.51849C10.1786 8.69039 10.082 8.92355 10.082 9.16667V11.9167C10.082 12.1598 10.1786 12.3929 10.3505 12.5648C10.5224 12.7368 10.7556 12.8333 10.9987 12.8333Z" fill="#DA5D28"/>
                        <path d="M10.9987 7.33333C11.505 7.33333 11.9154 6.92293 11.9154 6.41667C11.9154 5.91041 11.505 5.5 10.9987 5.5C10.4924 5.5 10.082 5.91041 10.082 6.41667C10.082 6.92293 10.4924 7.33333 10.9987 7.33333Z" fill="#DA5D28"/>
                    </svg>
                    <span><a href="' .  $this->escapeHtml($this->getModuleInfo()['documentation_url']) . '" target="_blank">User Guide</a></span>
                </span>
            </div>
        </div>
    </div>

    <div class="col-actions">
        <div class="actions">';
        if (!$this->getModuleVersion->execute($this->getModuleName() . $this->getModuleMaxPlan()) && $this->getModuleUpgradePlanUrl()) {
            $html .= '<button id="upgrade" title="Upgrade Plan" class="action-upgrade" onclick="window.open(\'' . $this->escapeHtml($this->getModuleUrl() . '/pricing') . '\', \'_blank\'); return false;"><span>Upgrade Plan</span></button>';
        }

        if ($newVersionAvailable) {
            $html .= '<button id="update" title="Upgrade to new Version" class="action-default update _action-primary" onclick="window.open(\'https://mage\' + \'fan.com/downloadable/customer/products\', \'_blank\'); return false;"><span>Upgrade to new Version</span></button>
            <div class="available-version">Version v' . $this->escapeHtml($this->getModuleInfo()['version']) . ' is available</div>';
        }
        $html .= '</div>
        </div>
    </div>';

        return $html;
    }

    /**
     * Return extension url
     * @return string
     */
    protected function getModuleUrl()
    {
        return 'https://magefan.com/';
    }

    /**
     * Return extension title
     * @return string
     */
    protected function getModuleTitle()
    {
        return ucwords(str_replace('_', ' ', $this->getModuleName())) . ' Extension';
    }

    /**
     * Return extension image
     * @return string
     */
    protected function getModuleImage() {
        return '';
    }

    /**
     * Return extension info
     * @return array
     */
    protected function getModuleInfo(): array
    {
        $data = (array)$this->extensionsInfo->getJsonObject();
        return (array)$data[$this->getModuleKey()];
    }

    /**
     * Return extension Module Key
     * @return string
     */
    protected function getModuleKey() {
        return '';
    }

    /**
     * Return extension Max Plan
     * @return string
     */
    protected function getModuleMaxPlan() {
        return '';
    }
}
