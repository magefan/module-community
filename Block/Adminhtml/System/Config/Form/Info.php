<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Block\Adminhtml\System\Config\Form;

use Magefan\Community\Api\GetModuleVersionInterface;
use Magefan\Community\Api\SecureHtmlRendererInterface;
use Magefan\Community\Api\GetModuleInfoInterface;

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
     * @var GetModuleInfoInterface|ModuleInfoInterface|mixed
     */
    protected $getModuleInfo;

    /**
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     * @param GetModuleVersionInterface|null $getModuleVersion
     * @param SecureHtmlRendererInterface|null $mfSecureRenderer
     * @param ModuleInfoInterface|null $getModuleInfo
     */
    public function __construct(
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        GetModuleVersionInterface $getModuleVersion = null,
        SecureHtmlRendererInterface $mfSecureRenderer = null,
        GetModuleInfoInterface $getModuleInfo = null
    ) {
        parent::__construct($context, $data);
        $this->moduleList = $moduleList;
        $this->getModuleVersion = $getModuleVersion ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magefan\Community\Api\GetModuleVersionInterface::class
        );
        $this->mfSecureRenderer = $mfSecureRenderer ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SecureHtmlRendererInterface::class);
        $this->getModuleInfo = $getModuleInfo ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(GetModuleInfoInterface::class);
    }

    /**
     * Return info block html
     * @param  \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $moduleName = $this->getModuleName();

        $currentVersion = $this->getModuleVersion->execute($moduleName);
        $moduleInfo = $this->getModuleInfo->execute($moduleName);

        $plan = '';
        foreach (['Extra', 'Plus'] as $_plan) {
            if ($_currentVersion = $this->getModuleVersion->execute($moduleName . $_plan)) {
                $plan = $_plan;
                $currentVersion = $_currentVersion;
                break;
            }
        }

        if ($latestVersion = $moduleInfo->getVersion()) {

            $fullModuleName = $moduleInfo->getProductName();
            $moduleUrl = $moduleInfo->getProductUrl();
            $moduleImage = $moduleInfo->getProductImage();

            $newVersionAvailable = version_compare($latestVersion, $currentVersion) > 0;
            $moduleName = str_replace(['Magento 2', 'Magento'], ['', ''], (string)$fullModuleName);
            $moduleName = trim($moduleName);

        } else {

            $fullModuleName = $moduleName = $this->getModuleTitle();
            $newVersionAvailable = false;
            $moduleUrl = $this->getModuleUrl();
            $moduleImage = '';
        }

        $utmParam = '?utm_source=admin&utm_medium=config&utm_campaign=' . $this->getModuleName();

        if ($moduleInfo->getMaxPlan()) {
            $canUpgradeToMaxPlan = !$this->getModuleVersion->execute($moduleName . ucfirst($moduleInfo->getMaxPlan()));
        } else {
            $canUpgradeToMaxPlan = false;
        }

        $html = '<div class="section-info">
        <div class="col-info">
            <div class="product-icon">
                <a title="' .$this->escapeHtml($fullModuleName) . '" href="' . $this->escapeHtml($moduleUrl) .  $utmParam . '&utm_content=product_image" target="_blank">
                    <img src="' .  $this->escapeHtml($moduleImage) . '" alt=""/>
                </a>
            </div>
            <div class="product-info-wrapper">
                <div class="row-1">
                    <div class="block-title">
                        ' .$this->escapeHtml($moduleName) . ($plan ? ' (' . $plan . ')' : '') . ' v' . $this->escapeHtml($currentVersion) . '
                    </div>
                </div>
                <div class="row-2">
                    <span class="block-dev">developed by 
                        <a href="' . $this->escapeHtml($moduleUrl) .  $utmParam . '&utm_content=magefan" target="_blank">Mage' . 'fan</a>
                    </span>
                    <span class="block-dot">&middot;</span>
                    <span class="block-guide">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 22 22" fill="none">
                            <path d="M6.41797 16.4987H15.5846C16.314 16.4987 17.0135 16.209 17.5292 15.6932C18.0449 15.1775 18.3346 14.478 18.3346 13.7487V4.58203C18.3346 3.85269 18.0449 3.15321 17.5292 2.63749C17.0135 2.12176 16.314 1.83203 15.5846 1.83203H6.41797C5.68862 1.83203 4.98915 2.12176 4.47343 2.63749C3.9577 3.15321 3.66797 3.85269 3.66797 4.58203V17.4154C3.66797 18.1447 3.9577 18.8442 4.47343 19.3599C4.98915 19.8756 5.68862 20.1654 6.41797 20.1654H17.418C17.6611 20.1654 17.8942 20.0688 18.0662 19.8969C18.2381 19.725 18.3346 19.4918 18.3346 19.2487C18.3346 19.0056 18.2381 18.7724 18.0662 18.6005C17.8942 18.4286 17.6611 18.332 17.418 18.332H6.41797C6.17485 18.332 5.9417 18.2355 5.76979 18.0635C5.59788 17.8916 5.5013 17.6585 5.5013 17.4154C5.5013 17.1723 5.59788 16.9391 5.76979 16.7672C5.9417 16.5953 6.17485 16.4987 6.41797 16.4987ZM5.5013 4.58203C5.5013 4.33892 5.59788 4.10576 5.76979 3.93385C5.9417 3.76194 6.17485 3.66536 6.41797 3.66536H15.5846C15.8278 3.66536 16.0609 3.76194 16.2328 3.93385C16.4047 4.10576 16.5013 4.33892 16.5013 4.58203V13.7487C16.5013 13.9918 16.4047 14.225 16.2328 14.3969C16.0609 14.5688 15.8278 14.6654 15.5846 14.6654H6.41797C6.10573 14.6652 5.79574 14.7182 5.5013 14.8221V4.58203Z" fill="#DA5D28"/>
                            <path d="M10.9987 12.8333C11.2418 12.8333 11.475 12.7368 11.6469 12.5648C11.8188 12.3929 11.9154 12.1598 11.9154 11.9167V9.16667C11.9154 8.92355 11.8188 8.69039 11.6469 8.51849C11.475 8.34658 11.2418 8.25 10.9987 8.25C10.7556 8.25 10.5224 8.34658 10.3505 8.51849C10.1786 8.69039 10.082 8.92355 10.082 9.16667V11.9167C10.082 12.1598 10.1786 12.3929 10.3505 12.5648C10.5224 12.7368 10.7556 12.8333 10.9987 12.8333Z" fill="#DA5D28"/>
                            <path d="M10.9987 7.33333C11.505 7.33333 11.9154 6.92293 11.9154 6.41667C11.9154 5.91041 11.505 5.5 10.9987 5.5C10.4924 5.5 10.082 5.91041 10.082 6.41667C10.082 6.92293 10.4924 7.33333 10.9987 7.33333Z" fill="#DA5D28"/>
                        </svg>
                        <span><a href="' .  $this->escapeHtml($moduleInfo->getDocumentationUrl()) . $utmParam . '" target="_blank">User Guide</a></span>
                    </span>
                </div>
            </div>
        </div>
    
        <div class="col-actions">
            <div class="actions">';
            if ($canUpgradeToMaxPlan) {
                $html .= '<button id="upgrade" title="Upgrade Plan" class="action-upgrade" onclick="window.open(\'' . $this->escapeHtml($moduleUrl . '/pricing'  . $utmParam) . '\', \'_blank\'); return false;"><span>Upgrade Plan</span></button>';
            }

            if ($newVersionAvailable) {
                $html .= '<button id="update" title="Upgrade to new Version" class="action-default update _action-primary" onclick="window.open(\'https://mage' . 'fan.com/downloadable/customer/products' . $utmParam . '\', \'_blank\'); return false;"><span>Upgrade to new Version</span></button>';
            }
            $html .= '</div>
           ';
           if ($newVersionAvailable) {
               $html .= '<div class="available-version">Version v' . $this->escapeHtml($latestVersion) . ' is available</div>';
           }
         $html .= ' </div>
</div>
        <style>
            .section-config a[id$="_general-head"] {display: none;}
            
            .section-info {display: flex;flex-wrap: wrap;justify-content: space-between;gap: 36px;border: 1px solid rgba(0,0,0,0.1);background: #F8F8F8; padding: 24px 24px;font-size: 18px;font-weight: 500;}
            .section-info .col-info {display: flex;align-items: flex-start;}
            .section-info .col-info .product-icon {width: 64px;height: 64px;margin-right: 24px;}
            .section-info .col-info .product-icon img {border-radius: 6px;}
            .section-info .row-1 {margin-bottom: 14px;}
            .section-info .row-1 .block-title {color: #000000;font-size: 24px;line-height: 32px;font-weight: 600;}
            .section-info .row-2 {display: flex;align-items: center;}
            .section-info .row-2 .block-dev {color: #98A2B3;}
            .section-info .row-2 .block-dev a {color: #DA5D28;}
            .section-info .row-2 .block-dot {margin: 0 12px;color: #98A2B3;}
            .section-info .row-2 .block-guide {display: flex;align-items: center;gap: 5px;}
            .section-info .row-2 .block-guide a {font-size: 18px;font-weight: 500;color: #DA5D28;}
            .section-info .col-actions {display: flex;flex-direction: column;align-items: flex-end;}
            .section-info .col-actions .actions {display: flex;align-items: center;gap: 28px;}
            .section-info .col-actions button {padding: 9px 16px 9px;text-align: center;position: relative;box-sizing: border-box;}
            .section-info .col-actions button span {font-size: 18px;font-weight: 500;line-height: 24px;vertical-align: baseline;}
            .section-info .col-actions button.upgrade {background: #ffffff;border: 1px solid #D0D5DD;box-shadow: 0px 1px 2px 0px rgba(16,24,40,0.05);}
            .section-info .col-actions button.update {color: #ffffff;background: #494542;padding-right: 56px;border-color: #494542;}
            .section-info .col-actions button.update:after {display: inline-block;content: "\e626";margin: 0;padding: 12px;transform: rotateY(180deg);position: absolute;top: -1px;right: -1px;background: #363330;-webkit-font-smoothing: antialiased;font-family: "Admin Icons";font-style: normal;font-weight: normal;font-size: 2rem;line-height: 1;speak: none;}
            .section-info .col-actions button.update:hover {border-color: #494542;}
            .section-info .col-actions .available-version {font-size: 16px;font-weight: 500;line-height: 24px;margin-top: 10px;margin-right: 36px;color: #8D8D8D;}
        </style>
        ';

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

}
