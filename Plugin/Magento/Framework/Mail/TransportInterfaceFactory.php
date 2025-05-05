<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Plugin\Magento\Framework\Mail;

use Magento\Framework\Mail\TransportInterfaceFactory as MailTransportInterfaceFactory;
use Magefan\Community\Model\Attachments;
use Magento\Framework\Mail\EmailMessage;
use Magefan\Community\Api\Data\Attachments\ItemInterface;

class TransportInterfaceFactory
{
    /**
     * @var Attachments
     */
    private $attachments;

    /**
     * @param Attachments $attachments
     */
    public function __construct(
        Attachments $attachments
    ) {
        $this->attachments = $attachments;
    }

    /**
     * @param MailTransportInterfaceFactory $subject
     * @param array $data
     * @return array[]
     */
    public function beforeCreate(MailTransportInterfaceFactory $subject, array $data = []): array
    {
        /** @var ItemInterface $item */
        $items = $this->attachments->getItems();

        if (!empty($items)) {
            /** @var EmailMessage $message */
            $message = $data['message'];

            if ($message instanceof \Magento\Framework\Mail\EmailMessage && method_exists($message, 'getSymfonyMessage')) {
                // Magento 2.4.8+ uses Symfony mailer
                $this->processFor248($message, $items);
            } else {
                // Magento 2.3.6 – 2.4.7 uses Laminas\Mail\Message)
                $this->processFor236_247($message, $items);
            }
        }

        return [$data];
    }

    /**
     * @param $message
     * @param $items
     * @return void
     */
    private function processFor236_247($message, $items): void
    {
        $parts = [];
        foreach ($items as $item) {
            $attachment = new \Laminas\Mime\Part($item->getContent());
            $attachment->type = $item->getType();
            $attachment->encoding = \Laminas\Mime\Mime::ENCODING_BASE64;
            $attachment->disposition = \Laminas\Mime\Mime::DISPOSITION_ATTACHMENT;
            $attachment->filename = $item->getName();

            $parts[] = $attachment;
        }
        $this->attachments->unsetItems();

        $body = \Laminas\Mail\Message::fromString($message->getRawMessage())->getBody();
        $body = quoted_printable_decode($body);

        $part = new \Laminas\Mime\Part($body);
        $part->setCharset('utf-8');
        $part->setEncoding(\Laminas\Mime\Mime::ENCODING_QUOTEDPRINTABLE);
        $part->setDisposition(\Laminas\Mime\Mime::DISPOSITION_INLINE);
        $part->setType(\Laminas\Mime\Mime::TYPE_HTML);

        array_unshift($parts, $part);

        $bodyPart = new \Laminas\Mime\Message();
        $bodyPart->setParts($parts);
        $message->setBody($bodyPart);
    }

    /**
     * @param $message
     * @return void
     */
    private function processFor248($message, $items)
    {
        $symfonyEmail = $message->getSymfonyMessage();

        // Decode existing body
        $body = $symfonyEmail->getBody()->bodyToString();
        $body = quoted_printable_decode($body);

        $htmlPart = new \Symfony\Component\Mime\Part\TextPart($body, 'utf-8', 'html');

        $parts = [$htmlPart];

        foreach ($items as $item) {
            $parts[] = new \Symfony\Component\Mime\Part\DataPart(
                $item->getContent(),
                $item->getName(),
                $item->getType()
            );
        }

        $this->attachments->unsetItems();

        // Set new multipart body
        $symfonyEmail->setBody(new \Symfony\Component\Mime\Part\Multipart\MixedPart(...$parts));
    }
}
