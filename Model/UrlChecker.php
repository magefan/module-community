<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

/*
 * Class with static methods to check URL
 */
class UrlChecker
{
    // @codingStandardsIgnoreStart
    /**
     * Show url
     *
     * @param string $url
     * @return bool
     */
    final public static function showUrl($url)
    {
        $url = (string)$url;
        $info = parse_url($url);
        $part = '';
        if (isset($info['host'])) {
            $part = explode('.', $info['host']);
            $part = $part[0];
        }

        if (!$part) {
            $part = 0;
        }

        return (false === strpos($url, 'mag' . 'ento'))
            && !is_numeric($part);
    }
    // @codingStandardsIgnoreEnd
}
