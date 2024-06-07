<?php

/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

interface SecureHtmlRendererInterface
{

    /**
     * @param string $tagName
     * @param array $attributes
     * @param string|null $content
     * @param bool $textContent
     * @return string
     */
    public function renderTag(
        string $tagName,
        array $attributes,
        ?string $content = null,
        bool $textContent = true
    );

}
