<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api\Data;

interface AttachmentsInterface
{

    /**
     * Add item
     * @param Attachments\ItemInterface $item
     * @return \Magefan\Community\Api\Data\AttachmentsInterface
     */
    public function addItem(Attachments\ItemInterface $item);

    /**
     * Get items
     * @return array
     */
    public function getItems();

    /**
     * Unset items
     * @return \Magefan\Community\Api\Data\AttachmentsInterface
     */
    public function unsetItems();
}
