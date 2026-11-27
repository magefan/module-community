<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\Admin;

use Magento\Backend\Model\UrlInterface as BackendUrlInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;

/**
 * Protect admin state-changing actions (save, delete, duplicate, mass status) from CSRF
 * while keeping existing GET links working when "Add Secret Key to URLs" is disabled.
 */
class RequestValidator
{
    /**
     * Sec-Fetch-Site values that can't be produced by a cross-site page.
     */
    private const ALLOWED_FETCH_SITES = ['same-origin', 'none'];

    /**
     * @var BackendUrlInterface
     */
    private $backendUrl;

    /**
     * @param BackendUrlInterface $backendUrl
     */
    public function __construct(
        BackendUrlInterface $backendUrl
    ) {
        $this->backendUrl = $backendUrl;
    }

    /**
     * Check whether a state-changing admin request may be executed.
     *
     * POST requests and requests with the URL secret key are already validated by Magento.
     * Other requests must carry a valid form key or come from the admin panel itself.
     *
     * @param RequestInterface $request
     * @return bool
     */
    public function isAllowed(RequestInterface $request): bool
    {
        if (!$request instanceof HttpRequest) {
            return false;
        }

        if ($request->isPost()) {
            /* Magento alrady has form key validation on POST */
            return true;
        }
            
        if ($this->backendUrl->useSecretKey()) {
            /* Magento alrady has validateion if secure key is enabled */
            return true;
        }

        return $this->isSameOrigin($request);
    }

    /**
     * Check that the request was initiated from the same origin.
     *
     * Sec-Fetch-Site is set by the browser and can't be forged by a web page.
     * Referer is used only for old browsers without Fetch Metadata; a missing Referer is rejected.
     *
     * @param HttpRequest $request
     * @return bool
     */
    private function isSameOrigin(HttpRequest $request): bool
    {
        $fetchSite = (string)$request->getHeader('Sec-Fetch-Site');
        if ($fetchSite !== '') {
            return in_array(strtolower($fetchSite), self::ALLOWED_FETCH_SITES, true);
        }

        $refererHost = $this->getHost((string)$request->getHeader('Referer'));
        if ($refererHost === '') {
            return false;
        }

        $allowedHosts = [
            strtolower((string)$request->getHttpHost()),
            $this->getHost((string)$this->backendUrl->getBaseUrl())
        ];

        return in_array($refererHost, array_filter($allowedHosts), true);
    }

    /**
     * Get lowercase host from URL.
     *
     * @param string $url
     * @return string
     */
    private function getHost(string $url): string
    {
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $host = $url ? parse_url($url, PHP_URL_HOST) : '';
        return $host ? strtolower((string)$host) : '';
    }
}
