<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\Community\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magefan\Community\Model\SectionFactory;
use Magefan\Community\Model\Section\Info;
use Magento\Framework\Message\ManagerInterface;
use Magefan\Community\Model\SetLinvFlag;
use Magefan\Community\Model\Config;
use Magefan\Community\Model\Section;

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
     * ConfigObserver constructor.
     * @param SectionFactory $sectionFactory
     * @param Info $info
     * @param ManagerInterface $messageManager
     * @param SetLinvFlag $setLinvFlag
     * @param Config $config
     */
    final public function __construct(
        SectionFactory $sectionFactory,
        Info $info,
        ManagerInterface $messageManager,
        SetLinvFlag $setLinvFlag,
        Config $config
    ) {
        $this->sectionFactory = $sectionFactory;
        $this->info = $info;
        $this->messageManager = $messageManager;
        $this->setLinvFlag = $setLinvFlag;
        $this->config = $config;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    final public function execute(\Magento\Framework\Event\Observer $observer)
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
        }
    }
}
