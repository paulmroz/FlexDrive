<?php

declare(strict_types=1);

namespace App\Car\Application\Service;

interface ChatPublisherInterface
{
    /**
     * @param array<array{carId: string, reason: string}> $suggestions
     */
    public function publish(string $sessionId, array $suggestions, ?string $message): void;
}
