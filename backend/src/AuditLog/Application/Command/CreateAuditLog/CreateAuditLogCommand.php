<?php

declare(strict_types=1);

namespace App\AuditLog\Application\Command\CreateAuditLog;

use DateTimeImmutable;

/**
 * @see CreateAuditLogCommandHandler
 */
class CreateAuditLogCommand
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $eventType,
        public readonly string $aggregateId,
        public readonly string $aggregateType,
        public readonly array $payload,
        public readonly ?string $userId,
        public readonly DateTimeImmutable $occurredAt
    ) {
    }
}
