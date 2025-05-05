<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api;

/**
 * Add Email Attachment
 *
 * @api
 * @since 2.2.9
 */
interface AddEmailAttachmentInterface
{

    /**
     * @param string $content
     * @param string $name
     * @param string $type
     * @return void
     */
    public function execute(string $content, string $name, string $type): void;

}
