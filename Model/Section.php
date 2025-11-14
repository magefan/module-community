<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\ProductMetadataInterface;

/**
 * Class Section
 * @package Magefan\Community\Model
 */
// @codingStandardsIgnoreLine
final class Section
{
    public const MODULE = 'mfmodule';

    public const ENABLED = 'enabled';

    public const KEY = 'key';

    public const TYPE = 'mftype';

    public const ACTIVE = 'mfactive';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var GetModuleVersion
     */
    private $getModuleVersion;

    /**
     * @var HyvaThemeDetection
     */
    private $hyvaThemeDetection;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $key;

    /**
     * @var ProductMetadataInterface
     */
    protected $metadata;

    // @codingStandardsIgnoreStart
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductMetadataInterface $metadata
     * @param GetModuleVersion $getModuleVersion
     * @param HyvaThemeDetection $hyvaThemeDetection
     * @param $name
     * @param $key
     */
    final public function __construct(
        ScopeConfigInterface $scopeConfig,
        ProductMetadataInterface $metadata,
        GetModuleVersion $getModuleVersion,
        HyvaThemeDetection $hyvaThemeDetection,
        $name = null,
        $key = null
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->metadata = $metadata;
        $this->getModuleVersion = $getModuleVersion;
        $this->hyvaThemeDetection = $hyvaThemeDetection;
        $this->name = $name;
        $this->key = $key;
    }

    /**
     * Check if enabled
     *
     * @return bool
     */
    final public function isEnabled()
    {
        return (bool)$this->getConfig(self::ENABLED);
    }

    /**
     * Get module name
     *
     * @param false $e
     * @return false|string
     */
    final public function getModuleName($e = false)
    {
        $fs = $e ? [self::MODULE] : [self::MODULE . 'e', self::MODULE . 'p', self::MODULE];
        foreach ($fs as $f) {
            $module = (string)$this->getConfig($f);
            if ($module) {
                break;
            }
        }

        return $module;
    }

    /**
     * Get module
     *
     * @param false $e
     * @return false|string
     */
    final public function getModule($e = false)
    {
        $module = $this->getModuleName();

        $url = $this->scopeConfig->getValue(
            'web/unsecure/base' . '_' . 'url',
            ScopeInterface::SCOPE_STORE,
            0
        );

        if (\Magefan\Community\Model\UrlChecker::showUrl($url)) {
            if ($module && $this->getType()) {
                return $module;
            }

            if ($module == ('B' . 'l' . 'o' . 'g')
                && version_compare($this->getModuleVersion->execute('Ma' . 'ge' . 'fa' . 'n_' . $module), '2.' . '11' . '.4', '>=')
                && $this->hyvaThemeDetection->execute()
            ) {
                return $module;
            }
        }
        return false;
    }

    /**
     * Get type
     *
     * @return bool
     */
    final public function getType()
    {
        return (!$this->getConfig(self::TYPE)
            || $this->getConfig(self::TYPE) && $this->metadata->getEdition() != 'C' . 'omm' . 'un' . 'ity'
        );
    }

    /**
     * Get key
     *
     * @return string
     */
    final public function getKey()
    {
        if (null !== $this->key) {
            return $this->key;
        } else {
            return $this->getConfig(self::KEY);
        }
    }

    /**
     * Get name
     *
     * @return string
     */
    final public function getName()
    {
        return (string) $this->name;
    }

    /**
     * Validate data
     *
     * @param $data
     * @return bool
     */
    final public function validate($data)
    {
        if (isset($data[$this->getModule()])) {
            return !empty($data[$this->getModule()]);
        }

        $k = $this->getKey();

        foreach ([$this->getModule(), $this->getModule(true)] as $id) {
            foreach (['', 'Plus', 'Extra'] as $e) {
                if ($result = $this->validateIDK($id . $e, $k)) {
                    return true;
                }
            }
        }

        return false;
    }
    // @codingStandardsIgnoreEnd

    /**
     * Validate
     *
     * @param string $id
     * @param string $k
     * @return bool
     */
    private function validateIDK($id, $k)
    {
        $l = substr($id, 1, 1);
        $d = (string) strlen($id);

        return (strlen($k) >= '3' . '2')
            && (strpos($k, $l, 5) == 5)
            && (strpos($k, $d, 19) == 19);
    }

    /**
     * Get config
     *
     * @param string $field
     * @return mixed
     */
    private function getConfig($field)
    {
        $g = 'general';
        return $this->scopeConfig->getValue(
            implode('/', [$this->name, $g, $field]),
            ScopeInterface::SCOPE_STORE,
            0
        );
    }
}
