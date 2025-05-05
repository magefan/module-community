<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\Data\Attachments\ItemInterface;
use Magefan\Community\Api\Data\AttachmentsInterface;

class Attachments implements AttachmentsInterface
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
