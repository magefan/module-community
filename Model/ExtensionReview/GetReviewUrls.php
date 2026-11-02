<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\ExtensionReview;

use Magefan\Community\Model\GetModuleInfo;

/**
 * Magefan URLs to request a review token and to submit a review for an extension.
 *
 * Shared by the review popup block and controller, so the popup is shown only when the review can be submitted.
 */
class GetReviewUrls
{
    /**
     * @var GetModuleInfo
     */
    private $getModuleInfo;

    /**
     * @param GetModuleInfo $getModuleInfo
     */
    public function __construct(
        GetModuleInfo $getModuleInfo
    ) {
        $this->getModuleInfo = $getModuleInfo;
    }

    /**
     * Get ['token' => url, 'review' => url], or an empty array if any of them is missing or not http(s)
     *
     * @param string $moduleName module name with or without vendor prefix, e.g. Magefan_Blog or Blog
     * @return array
     */
    public function execute(string $moduleName): array
    {
        if (!$moduleName) {
            return [];
        }

        $info = $this->getModuleInfo->execute($moduleName);
        $urls = [
            'token' => (string)$info->getData('popup_review_token_url'),
            'review' => (string)$info->getData('popup_review_url'),
        ];

        foreach ($urls as $url) {
            if (!preg_match('#^https?://#i', $url)) {
                return [];
            }
        }

        return $urls;
    }
}
