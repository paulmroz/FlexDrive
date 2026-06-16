<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Entity;

use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use App\Subscription\Domain\Transition\SubscriptionTransitionInterface;
use App\Subscription\Domain\ValueObject\SagaStatus;
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

    #[ORM\Column(type: 'string', length: 30, enumType: SagaStatus::class, nullable: true)]
    private ?SagaStatus $sagaStatus = null;

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

    public function applyTransition(SubscriptionTransitionInterface $transition): void
    {
        $mutator = function(SubscriptionStatus $newStatus, ?DateTimeImmutable $endDate = null): void {
            $this->status = $newStatus;
            if (null !== $endDate) {
                $this->endDate = $endDate;
            }
        };

        $transition->apply(subscription: $this, mutator: $mutator);
    }

    public function setSagaStatus(SagaStatus $status): void
    {
        $validTransitions = [
            SagaStatus::PENDING_LOCK->value => [SagaStatus::LOCKED, SagaStatus::COMPENSATION_REQUIRED],
            SagaStatus::LOCKED->value => [SagaStatus::COMPENSATION_REQUIRED, SagaStatus::COMPLETED],
            SagaStatus::COMPENSATION_REQUIRED->value => [SagaStatus::COMPLETED],
        ];

        if (null !== $this->sagaStatus) {
            $allowed = $validTransitions[$this->sagaStatus->value] ?? [];
            if (!in_array(needle: $status, haystack: $allowed, strict: true)) {
                throw new DomainException(message: sprintf('Invalid saga transition from %s to %s', $this->sagaStatus->value, $status->value));
            }
        }

        $this->sagaStatus = $status;
    }

    public function getSagaStatus(): ?SagaStatus
    {
        return $this->sagaStatus;
    }
}
