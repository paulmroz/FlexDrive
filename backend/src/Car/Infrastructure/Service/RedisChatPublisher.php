<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Service;

use App\Car\Application\Service\ChatPublisherInterface;
use Redis;

class RedisChatPublisher implements ChatPublisherInterface
{
    public function __construct(
        private readonly Redis $redis
    ) {
    }

    public function publish(string $sessionId, array $suggestions, ?string $message): void
    {
        $payload = json_encode(value: [
            'status' => 'completed',
            'suggestions' => $suggestions,
            'message' => $message,
        ]);

        $this->redis->setex(
            key: 'chat_result.' . $sessionId,
            expire: 300,
            value: $payload
        );

        $this->redis->publish(
            channel: 'chat.' . $sessionId,
            message: $payload
        );
    }
}
