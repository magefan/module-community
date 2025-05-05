<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api\Data;

interface EmailAttachmentsInterface
{

    /**
     * Add item
     * @param EmailAttachments\ItemInterface $item
     * @return \Magefan\Community\Api\Data\EmailAttachmentsInterface
     */
    public function addItem(EmailAttachments\ItemInterface $item);

    /**
     * Get items
     * @return array
     */
    public function getItems();

    /**
     * Unset items
     * @return \Magefan\Community\Api\Data\EmailAttachmentsInterface
     */
    public function unsetItems();
}
