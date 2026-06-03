<?php

declare(strict_types=1);

namespace App\Car\Application\Command;

use App\Car\Application\Command\ProcessChatMessageCommandHandler;

/**
 * @see ProcessChatMessageCommandHandler
 */
class ProcessChatMessageCommand
{
    public function __construct(
        private readonly string $sessionId,
        private readonly string $messageContent
    ) {
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getMessageContent(): string
    {
        return $this->messageContent;
    }
}
