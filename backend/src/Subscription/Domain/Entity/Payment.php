<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Entity;

use App\Shared\Domain\AggregateRoot;
use App\Subscription\Domain\Event\PaymentCompletedEvent;
use App\Subscription\Domain\Event\PaymentConflictDetectedEvent;
use App\Subscription\Domain\Event\PaymentDisputedEvent;
use App\Subscription\Domain\Event\PaymentFailedEvent;
use App\Subscription\Domain\Event\PaymentRefundedEvent;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: '`payments`')]
class Payment extends AggregateRoot
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $sessionId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $subscriptionId;

    #[ORM\Column(type: 'integer')]
    private int $amount;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;

    #[ORM\Column(type: 'string', length: 30, enumType: PaymentStatus::class)]
    private PaymentStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paidAt;

    public function __construct(
        string $id,
        string $sessionId,
        string $subscriptionId,
        int $amount,
        string $currency,
        PaymentStatus $status = PaymentStatus::CREATED
    ) {
        $this->id = $id;
        $this->sessionId = $sessionId;
        $this->subscriptionId = $subscriptionId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
        $this->paidAt = null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function markAsPaid(DateTimeImmutable $paidAt): void
    {
        if (PaymentStatus::CREATED !== $this->status && PaymentStatus::FAILED !== $this->status) {
            throw new DomainException(message: 'Payment cannot be marked as paid from its current state.');
        }

        $this->status = PaymentStatus::PAID;
        $this->paidAt = $paidAt;

        $this->recordEvent(event: new PaymentCompletedEvent(
            paymentId: $this->id,
            subscriptionId: $this->subscriptionId
        ));
    }

    public function markAsFailed(): void
    {
        if (PaymentStatus::CREATED !== $this->status) {
            throw new DomainException(message: 'Only pending payments can be marked as failed.');
        }

        $this->status = PaymentStatus::FAILED;

        $this->recordEvent(event: new PaymentFailedEvent(
            paymentId: $this->id,
            subscriptionId: $this->subscriptionId
        ));
    }

    public function markAsRefunded(): void
    {
        if (PaymentStatus::PAID !== $this->status) {
            throw new DomainException(message: 'Only paid payments can be refunded.');
        }

        $this->status = PaymentStatus::REFUNDED;

        $this->recordEvent(event: new PaymentRefundedEvent(
            paymentId: $this->id,
            subscriptionId: $this->subscriptionId
        ));
    }

    public function markAsDisputed(): void
    {
        if (PaymentStatus::PAID !== $this->status) {
            throw new DomainException(message: 'Only paid payments can be disputed.');
        }

        $this->status = PaymentStatus::DISPUTED;

        $this->recordEvent(event: new PaymentDisputedEvent(
            paymentId: $this->id,
            subscriptionId: $this->subscriptionId
        ));
    }

    public function markAsConflict(): void
    {
        if (PaymentStatus::CREATED !== $this->status && PaymentStatus::FAILED !== $this->status) {
            throw new DomainException(message: 'Conflict status can only be set on pending or failed payments.');
        }

        $this->status = PaymentStatus::PAID_CONFLICT;

        $this->recordEvent(event: new PaymentConflictDetectedEvent(
            paymentId: $this->id,
            subscriptionId: $this->subscriptionId
        ));
    }
}
