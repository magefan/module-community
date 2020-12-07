<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types = 1);

namespace Magefan\Community\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Config\Model\Config\Structure;
use Magento\Framework\Module\Manager;
use Magento\Framework\Module\ModuleListInterface;

class Activate extends Command
{
    const EXTENSION_NAME = 'extensionName';
    const LICENSE_KEY = 'licenseKey';

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var Structure
     */
    private $structure;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * Activate constructor.
     *
     * @param WriterInterface $configWriter
     * @param Structure $structure
     * @param ModuleListInterface $moduleList
     * @param Manager $moduleManager
     * @param string|null $name
     */
    public function __construct(
        WriterInterface $configWriter,
        Structure $structure,
        ModuleListInterface $moduleList,
        Manager $moduleManager,
        string $name = null
    ) {
        parent::__construct($name);
        $this->configWriter = $configWriter;
        $this->structure = $structure;
        $this->moduleList = $moduleList;
        $this->moduleManager = $moduleManager;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('magefan:license:activate')
            ->setDescription('This is the console command to activate Magefan extensions license.')
            ->addOption(
            self::EXTENSION_NAME,
            null,
            InputOption::VALUE_REQUIRED,
            'Extension Name'
            )
            ->addOption(
            self::LICENSE_KEY,
            null,
            InputOption::VALUE_REQUIRED,
            'Extension License Key'
            );
        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extensionName = $input->getOption(self::EXTENSION_NAME);
        $licenseKey = $input->getOption(self::LICENSE_KEY);

        if ($extensionName) {
            $output->writeln('<info>Provided extension name is `' . $extensionName . '`</info>');
        } elseif ($licenseKey) {
            $output->writeln('<info>Provided license key is `' . $licenseKey . '`</info>');
        }

        $output->writeln('<info>Success Message.</info>');
        $output->writeln('<error>An error encountered.</error>');
        $output->writeln('<comment>Some Comment.</comment>');

        /*
        if (in_array($extensionName, $this->getMagefanModules())) {
            $this->configWriter->save('my/path/whatever',  $extensionName, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            $this->configWriter->save('my/path/whatever',  $licenseKey, $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $scopeId = 0);
            $this->getConfigSections();
        }
        */
    }

    /**
     * Retrieve Magefan modules info
     *
     * @return array
     */
    private function getMagefanModules()
    {
        $modules = [];
        foreach ($this->moduleList->getNames() as $moduleName) {
            if (strpos($moduleName, 'Magefan_') !== false && $this->moduleManager->isEnabled($moduleName)) {
                $modules[] = $moduleName;
            }
        }
        return $modules;
    }

    private function getConfigSections()
    {
        $sections = [];
        if (count($sections)) {
            $tabs = $this->structure->getTabs();
            foreach ($tabs as $tab) {
                if ($tab->getId() == 'magefan') {
                    $sections = $tab->getData()['children'];
                    break;
                }
            }
            var_dump($sections);
        }

        exit;

        return $sections;
    }
}
