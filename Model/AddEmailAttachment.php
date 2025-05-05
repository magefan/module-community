<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

use Magefan\Community\Api\AddEmailAttachmentInterface;
use Magefan\Community\Api\Data\EmailAttachmentsInterface;
use Magefan\Community\Api\Data\EmailAttachments\ItemInterfaceFactory;

class AddEmailAttachment implements AddEmailAttachmentInterface
{
    /**
     * @var EmailAttachmentsInterface 
     */
    private $attachments;

    /**
     * @var ItemInterfaceFactory 
     */
    private $itemFactory;

    /**
     * @param EmailAttachmentsInterface $attachments
     * @param ItemInterfaceFactory $itemFactory
     */
    public function __construct(
        EmailAttachmentsInterface $attachments,
        ItemInterfaceFactory $itemFactory,
    ) {
        $this->attachments = $attachments;
        $this->itemFactory = $itemFactory;
    }

    /**
     * @param string $content
     * @param string $name
     * @param string $type
     * @return void
     */
    public function execute(string $content, string $name, string $type): void
    {
        $item = $this->itemFactory->create();
        $item->setContent($content)
            ->setName($name)
            ->setType($type);

        $this->attachments->addItem($item);
    }
}
