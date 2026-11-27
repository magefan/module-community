<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magefan\Community\Model\SectionFactory;
use Magefan\Community\Model\Section\Info;
use Magento\Framework\Message\ManagerInterface;
use Magefan\Community\Model\SetLinvFlag;
use Magefan\Community\Model\Config;
use Magefan\Community\Model\Section;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Community observer
 */
class ConfigObserver implements ObserverInterface
{
    /**
     * @var SectionFactory
     */
    private $sectionFactory;

    /**
     * @var Info
     */
    private $info;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var SetLinvFlag
     */
    private $setLinvFlag;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $configWriter;

    /**
     * @var Structure
     */
    private $configStructure;

    // @codingStandardsIgnoreStart
    /**
     * ConfigObserver constructor.
     *
     * @param SectionFactory $sectionFactory
     * @param Info $info
     * @param ManagerInterface $messageManager
     * @param SetLinvFlag $setLinvFlag
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param WriterInterface $configWriter
     * @param Structure $configStructure
     */
    final public function __construct(
        SectionFactory $sectionFactory,
        Info $info,
        ManagerInterface $messageManager,
        SetLinvFlag $setLinvFlag,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        Structure $configStructure
    ) {
        $this->sectionFactory = $sectionFactory;
        $this->info = $info;
        $this->messageManager = $messageManager;
        $this->setLinvFlag = $setLinvFlag;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->configStructure = $configStructure;
    }
    // @codingStandardsIgnoreEnd

    // @codingStandardsIgnoreStart
    /**
     * Check config
     *
     * @param Observer $observer
     */
    final public function execute(Observer $observer)
    {
        $request = $observer->getEvent()->getRequest();
        $groups = $request->getParam('groups');
        if (empty($groups['general']['fields']['enabled']['value'])) {
            return;
        }

        $key = isset($groups['general']['fields']['key']['value'])
            ? $groups['general']['fields']['key']['value']
            : null;

        $section = $this->sectionFactory->create([
            'name' => $request->getParam('section'),
            'key' => $key
        ]);

        if (!$section->getModule()) {
            $bp = $section->getName() . '/' . 'g' . 'e' . 'n' . 'e' . 'r' . 'a' . 'l' . '/' ;
            if (!$this->config->getConfig($bp . Section::ACTIVE) && !$section->getType()) {
                $this->messageManager->addError(
                    implode(array_reverse(
                        [
                            '.','e','g','a','s','u',' ','e','e','r','f',' ','y','o','j','n','e',' ',
                            'o','t',' ','t','i',' ','e','t','a','v','i','t','c','a',' ','e','s','a',
                            'e','l','P',' ','.','d','e','t','a','v','i','t','c','a',' ','t','o','n',
                            ' ','s','i',' ','n','o','i','s','n','e','t','x','e',' ','e','h','T'
                        ]
                    ))
                );
                $groups['general']['fields']['enabled']['value'] = 0;
                $request->setPostValue('groups', $groups);
            }
            return;
        }
        $data = $this->info->load([$section]);
        $errorMessage = $data[$section->getModule() . '_errorMsg'] ?? '';

        if (!$section->validate($data)) {
            $groups['general']['fields']['enabled']['value'] = 0;

            $this->setLinvFlag->execute($section->getName(), 1, $errorMessage);
            $request->setPostValue('groups', $groups);

            if (!$errorMessage) {
                $errorMessage = implode(array_reverse(
                    [
                        '.','d','e','l','b','a','s','i','d',' ','y','l','l','a','c','i','t','a','m',
                        'o','t','u','a',' ','n','e','e','b',' ','s','a','h',' ','n','o','i','s','n',
                        'e','t','x','e',' ','e','h','T',' ','.','d','i','l','a','v','n','i',' ','r',
                        'o',' ','y','t','p','m','e',' ','s','i',' ','y','e','K',' ','t','c','u','d',
                        'o','r','P'
                    ]
                ));
            }
            $this->messageManager->addError($errorMessage);
        } else {
            $errorMessage = $data[$section->getModule() . '_errorMsg'] ?? '';
            if ($errorMessage) {
                $this->messageManager->addError($errorMessage);
            }
            $this->setLinvFlag->execute($section->getName(), 0, $errorMessage);
            if (!$errorMessage) {
                $this->applyKeyToBundle($section, (string)$key);
            }
        }
    }
    // @codingStandardsIgnoreEnd

    /**
     * Check if product key was issued for the module (same check as in Section::validate)
     *
     * @param string $key
     * @param string $module
     * @return bool
     */
    private function isKeyForModule(string $key, string $module): bool
    {
        return strlen($key) >= 32
            && strpos($key, substr($module, 1, 1), 5) === 5
            && strpos($key, (string)strlen($module), 19) === 19;
    }

    /**
     * Save valid product key to all sections of the same bundle (e.g. all modules of SEO Extra)
     *
     * @param Section $section
     * @param string $key
     * @return void
     */
    private function applyKeyToBundle(Section $section, string $key): void
    {
        $bundleModule = $section->getModuleName();
        if (!$key || !preg_match('/^(.+)(Plus|Extra)$/', $bundleModule, $matches)
            || (!$this->isKeyForModule($key, $matches[1] . 'Plus') && !$this->isKeyForModule($key, $matches[1] . 'Extra'))
        ) {
            /* Key is not a bundle (plan) key, e.g. key of a single module like XML Sitemap */
            return;
        }

        $defaultConfig = $this->scopeConfig->getValue(null, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        if (!is_array($defaultConfig)) {
            return;
        }

        $updated = [];
        foreach ($defaultConfig as $sectionName => $sectionData) {
            if ($sectionName === $section->getName() || !isset($sectionData['general'][Section::MODULE])) {
                continue;
            }

            $bundleSection = $this->sectionFactory->create(['name' => $sectionName]);
            if ($bundleSection->getModuleName() !== $bundleModule || $bundleSection->getKey() === $key) {
                continue;
            }

            $this->configWriter->save(
                $sectionName . '/general/' . Section::KEY,
                $key,
                ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
                0
            );
            $this->setLinvFlag->execute($sectionName, 0);

            $element = $this->configStructure->getElement($sectionName);
            $updated[] = $element && $element->getLabel() ? $element->getLabel() : $sectionName;
        }

        if ($updated) {
            $this->messageManager->addSuccessMessage(
                __('The Product Key has also been saved for: %1.', implode(', ', $updated))
            );
        }
    }
}
