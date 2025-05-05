<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model\EmailAttachments;

use Magefan\Community\Api\Data\EmailAttachments\ItemInterface;
use Magefan\Community\Api\Data\EmailAttachmentsInterface;

class Attachments implements EmailAttachmentsInterface
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * @param ItemInterface $item
     * @return $this
     */
    public function addItem(ItemInterface $item): Attachments
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return $this
     */
    public function unsetItems(): Attachments
    {
        foreach ($this->items as $key => $item) {
            unset($this->items[$key]);
        }
        $this->items = [];

        return $this;
    }
}
