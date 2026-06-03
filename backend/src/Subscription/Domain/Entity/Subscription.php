<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Entity;

use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use DomainException;

#[ORM\Entity]
#[ORM\Table(name: '`subscriptions`')]
class Subscription
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $userId;

    #[ORM\Column(type: 'string', length: 36)]
    private string $carId;

    #[ORM\Column(type: 'string', length: 30, enumType: SubscriptionStatus::class)]
    private SubscriptionStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startDate;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endDate;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct(
        SubscriptionId $id,
        UserId $userId,
        CarId $carId,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate = null,
        SubscriptionStatus $status = SubscriptionStatus::PENDING_PAYMENT
    ) {
        $this->id = $id->getValue();
        $this->userId = $userId->getValue();
        $this->carId = $carId->getValue();
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): SubscriptionId
    {
        return new SubscriptionId(value: $this->id);
    }

    public function getUserId(): UserId
    {
        return new UserId(value: $this->userId);
    }

    public function getCarId(): CarId
    {
        return new CarId(value: $this->carId);
    }

    public function getStatus(): SubscriptionStatus
    {
        return $this->status;
    }

    public function getStartDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): ?DateTimeImmutable
    {
        return $this->endDate;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function activate(): void
    {
        if (SubscriptionStatus::PENDING_PAYMENT !== $this->status) {
            throw new DomainException(message: 'Only pending subscriptions can be activated.');
        }

        $this->status = SubscriptionStatus::ACTIVE;
    }

    public function cancel(DateTimeImmutable $cancelledAt): void
    {
        if (SubscriptionStatus::CANCELLED === $this->status || SubscriptionStatus::EXPIRED === $this->status) {
            throw new DomainException(message: 'Subscription is already ended and cannot be cancelled.');
        }

        $this->status = SubscriptionStatus::CANCELLED;
        $this->endDate = $cancelledAt;
    }

    public function reactivate(): void
    {
        if (SubscriptionStatus::CANCELLED !== $this->status) {
            throw new DomainException(message: 'Only cancelled subscriptions can be reactivated.');
        }

        $this->status = SubscriptionStatus::ACTIVE;
    }
}
