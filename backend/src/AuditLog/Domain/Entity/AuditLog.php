<?php

declare(strict_types=1);

namespace App\AuditLog\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: '`audit_logs`')]
class AuditLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 100)]
    private string $eventType;

    #[ORM\Column(type: 'string', length: 36)]
    private string $aggregateId;

    #[ORM\Column(type: 'string', length: 50)]
    private string $aggregateType;

    /** @var array<string, mixed> */
    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $userId;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        string $id,
        string $eventType,
        string $aggregateId,
        string $aggregateType,
        array $payload,
        ?string $userId,
        DateTimeImmutable $occurredAt
    ) {
        $this->id = $id;
        $this->eventType = $eventType;
        $this->aggregateId = $aggregateId;
        $this->aggregateType = $aggregateType;
        $this->payload = $payload;
        $this->userId = $userId;
        $this->occurredAt = $occurredAt;
    }

    public function getId(): string
    {
        return $this->id;
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

    /**
     * @return array<string, mixed>
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
