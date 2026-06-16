<?php

declare(strict_types=1);

namespace App\Shared\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

#[ORM\Entity]
#[ORM\Table(name: '`outbox_messages`')]
class OutboxMessage
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    private string $type;

    #[ORM\Column(type: 'text')]
    private string $payload;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $processedAt = null;

    #[ORM\Column(type: 'integer')]
    private int $retryCount = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastError = null;

    public function __construct(string $type, string $payload)
    {
        $this->id = (string) Uuid::v4();
        $this->type = $type;
        $this->payload = $payload;
        $this->createdAt = new DateTimeImmutable();
    }
}
