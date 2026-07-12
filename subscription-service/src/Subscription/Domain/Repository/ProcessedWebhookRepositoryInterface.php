<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\ProcessedWebhook;

interface ProcessedWebhookRepositoryInterface
{
    public function save(ProcessedWebhook $processedWebhook): void;

    public function findByEventId(string $eventId): ?ProcessedWebhook;
}
