<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface AuditableEventInterface
{
    public function getEventType(): string;

    public function getAggregateId(): string;

    public function getAggregateType(): string;

    /**
     * @return array<string, mixed>
     */
    public function getAuditPayload(): array;
}
