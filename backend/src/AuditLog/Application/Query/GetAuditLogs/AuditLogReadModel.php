<?php

declare(strict_types=1);

namespace App\AuditLog\Application\Query\GetAuditLogs;

class AuditLogReadModel
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        public readonly string $id,
        public readonly string $eventType,
        public readonly string $aggregateId,
        public readonly string $aggregateType,
        public readonly array $payload,
        public readonly ?string $userId,
        public readonly string $occurredAt
    ) {
    }
}
