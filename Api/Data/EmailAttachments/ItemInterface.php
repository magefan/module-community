<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Api\Data\EmailAttachments;

interface ItemInterface
{

    /**
     * Get content
     * @return string
     */
    public function getContent();

    /**
     * Set content
     * @param string $content
     * @return \Magefan\Community\Api\Data\EmailAttachments\ItemInterface
     */
    public function setContent(string $content);

    /**
     * Get name
     * @return string
     */
    public function getName();

    /**
     * Set name
     * @param string $name
     * @return \Magefan\Community\Api\Data\EmailAttachments\ItemInterface
     */
    public function setName(string $name);

    /**
     * Get type
     * @return string
     */
    public function getType();

    /**
     * Set type
     * @param string $type
     * @return \Magefan\Community\Api\Data\EmailAttachments\ItemInterface
     */
    public function setType(string $type);
}
