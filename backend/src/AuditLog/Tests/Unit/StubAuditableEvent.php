<?php

declare(strict_types=1);

namespace App\AuditLog\Tests\Unit;

use App\Shared\Domain\Event\AuditableEventInterface;

class StubAuditableEvent implements AuditableEventInterface
{
    /**
     * @param array<string, mixed> $auditPayload
     */
    public function __construct(
        private readonly string $eventType,
        private readonly string $aggregateId,
        private readonly string $aggregateType,
        private readonly array $auditPayload
    ) {
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getAuditPayload(): array
    {
        return $this->auditPayload;
    }
}
