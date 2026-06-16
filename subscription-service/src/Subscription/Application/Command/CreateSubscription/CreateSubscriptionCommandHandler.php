<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateSubscription;

use App\Car\Api\CarApiInterface;
use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Application\Message\RetryCompensationMessage;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use App\Shared\Domain\Entity\OutboxMessage;
use App\Shared\Domain\Repository\OutboxMessageRepositoryInterface;
use App\Subscription\Domain\ValueObject\SagaStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use DomainException;
use Throwable;

#[AsMessageHandler]
class CreateSubscriptionCommandHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly CarApiInterface $carApi,
        private readonly OutboxMessageRepositoryInterface $outboxRepository
    ) {
    }

    public function __invoke(CreateSubscriptionCommand $command): void
    {
        $carId = new CarId(value: $command->carId);
        $sagaId = (string) Uuid::v4();

        // 1. Validate Overlaps FIRST
        $overlapping = $this->subscriptionRepository->findOverlappingSubscriptions(
            carId: $carId,
            startDate: $command->startDate,
            endDate: $command->endDate
        );

        if ([] !== $overlapping) {
            throw new DomainException(message: 'Car is already booked/unavailable.');
        }

        // 2. Persist Initial State
        $subscription = new Subscription(
            id: new SubscriptionId(value: $command->subscriptionId),
            userId: new UserId(value: $command->userId),
            carId: $carId,
            startDate: $command->startDate,
            endDate: $command->endDate,
            status: SubscriptionStatus::PENDING_PAYMENT
        );
        $subscription->setSagaStatus(status: SagaStatus::PENDING_LOCK);
        $this->subscriptionRepository->save(subscription: $subscription);

        // 3. Request Remote Lock
        $this->carApi->lockAndValidateCar(carId: $command->carId, sagaId: $sagaId);

        // 4. Update state to LOCKED
        try {
            $subscription->setSagaStatus(status: SagaStatus::LOCKED);
            $this->subscriptionRepository->save(subscription: $subscription);
        } catch (Throwable $exception) {
            // 5. Compensation: Local DB failed to update, so we rollback the remote lock
            try {
                $this->carApi->unlockCar(carId: $command->carId, sagaId: $sagaId);
            } catch (Throwable $compensationException) {
                // ADDED: Outbox Pattern
                $payload = json_encode(value: ['carId' => $command->carId, 'sagaId' => $sagaId]);
                $outboxMessage = new OutboxMessage(
                    type: RetryCompensationMessage::class, 
                    payload: $payload
                );
                
                $this->outboxRepository->save(message: $outboxMessage);
            }
            throw $exception;
        }
    }
}
