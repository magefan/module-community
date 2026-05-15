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
        $d = $info['h'.'o'.'s'.'t'];
        $part = explode('.', $d)[0] ?? '';
        if (is_numeric($part)) {
            return true;
        }

        $f = 's'.'t'.'r'.'r'.'e'.'v';
        foreach (['o'.'i'.'.'.'i'.'l'.'c'.'x'.'n'.'.', 't'.'e'.'n'.'.'.'d'.'u'.'o'.'l'.'c'.'e'.'g'.'a'.'m'.'.', 'o'.'c'.'.'.'y'.'l'.'f'.'s'.'i'.'t'.'a'.'s'.'.', 'o'.'i'.'.'.'a'.'v'.'y'.'h'.'.'] as $endsWith) {
            if (str_ends_with($d, $f($endsWith))) {
                return true;
            }
        }

        foreach (['o'.'m'.'e'.'d', 'g'.'n'.'i'.'g'.'a'.'t'.'s'.'c'.'m', '1'.'g'.'n'.'i'.'g'.'a'.'t'.'s'.'c'.'m', '2'.'g'.'n'.'i'.'g'.'a'.'t'.'s'.'c'.'m', '3'.'g'.'n'.'i'.'g'.'a'.'t'.'s'.'c'.'m', '4'.'g'.'n'.'i'.'g'.'a'.'t'.'s'.'c'.'m', 'g'.'n'.'i'.'g'.'a'.'t'.'s', 'e'.'g'.'a'.'t'.'s', 'v'.'e'.'d', 't'.'s'.'e'.'t', 'l'.'a'.'c'.'o'.'l', 'l'.'a'.'c'.'o'.'l'.'c'.'m', 'd'.'o'.'r'.'c'.'p'.'m', 'g'.'t'.'s'.'c'.'m', '1'.'g'.'t'.'s'.'c'.'m', '2'.'g'.'t'.'s'.'c'.'m', '3'.'g'.'t'.'s'.'c'.'m', '4'.'g'.'t'.'s'.'c'.'m', 'd'.'l'.'o', 'd'.'o'.'r'.'p'.'e'.'r'.'p', 'g'.'t'.'s', 'r'.'e'.'k'.'c'.'o'.'d', 'a'.'t'.'e'.'b', 'o'.'t'.'n'.'e'.'g'.'a'.'m'] as $key) {
            $key = $f($key);

            if (0 === strpos($d, $key . '.')) {
                return true;
            }
            if (0 === strpos($d, $key . '-')) {
                return true;
            }

            if (false !== strpos($d, '-' . $key . '-')) {
                return true;
            }
            if (false !== strpos($d, '-' . $key . '.')) {
                return true;
            }
            if (false !== strpos($d, '.' . $key . '.')) {
                return true;
            }
        }

        $zone = explode('.', $d);
        $zone = end($zone);
        return in_array($f($zone), ['v'.'e'.'d', 'c'.'o'.'l', 'l'.'a'.'c'.'o'.'l', 't'.'s'.'o'.'h'.'l'.'a'.'c'.'o'.'l', 't'.'s'.'e'.'t']);
    }
    // @codingStandardsIgnoreEnd
}
