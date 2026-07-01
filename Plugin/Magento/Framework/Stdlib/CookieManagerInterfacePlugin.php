<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Framework\Stdlib;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\SensitiveCookieMetadata;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class CookieManagerInterfacePlugin
{
    /**
     * Request parameter that tells Magefan to skip setting the PHP session cookie in the response.
     */
    const NO_PHP_COOKIE_PARAM = 'magefan_no_php_cookie_resp';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Skip setting the sensitive (session) cookie for flagged Magefan requests.
     *
     * @param CookieManagerInterface $subject
     * @param callable $proceed
     * @param string $name
     * @param string $value
     * @param SensitiveCookieMetadata|null $metadata
     * @return void
     */
    public function aroundSetSensitiveCookie(
        CookieManagerInterface $subject,
        callable $proceed,
        $name,
        $value,
        ?SensitiveCookieMetadata $metadata = null
    ) {
        if ($this->shouldSkip($name)) {
            return;
        }

        return $proceed($name, $value, $metadata);
    }

    /**
     * Skip setting the public cookie for flagged Magefan requests.
     *
     * @param CookieManagerInterface $subject
     * @param callable $proceed
     * @param string $name
     * @param string $value
     * @param PublicCookieMetadata|null $metadata
     * @return void
     */
    public function aroundSetPublicCookie(
        CookieManagerInterface $subject,
        callable $proceed,
        $name,
        $value,
        ?PublicCookieMetadata $metadata = null
    ) {
        if ($this->shouldSkip($name)) {
            return;
        }

        return $proceed($name, $value, $metadata);
    }

    /**
     * Whether the PHP session cookie should be skipped for the current request.
     *
     * @param string $name
     * @return bool
     */
    private function shouldSkip(string $name): bool
    {
        return $name === 'PHPSESSID'
            && false !== strpos((string)$this->request->getRequestUri(), self::NO_PHP_COOKIE_PARAM);
    }
}