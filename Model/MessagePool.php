<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

declare(strict_types=1);

namespace Magefan\Community\Model;

class MessagePool
{
    /**
     * messages objects
     *
     * @var []
     */
    private $messages;

    /**
     * messagePool constructor.
     * @param array $messages
     */
    public function __construct(array $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @param string $actionName
     * @return array
     */
    public function getAll(string $actionName):array
    {
        return $this->messages[$actionName] ?? [];
    }
}