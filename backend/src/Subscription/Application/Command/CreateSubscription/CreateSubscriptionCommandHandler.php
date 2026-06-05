<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateSubscription;

use App\Car\Api\CarApiInterface;
use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use DomainException;

#[AsMessageHandler]
class CreateSubscriptionCommandHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly CarApiInterface $carApi
    ) {
    }

    public function __invoke(CreateSubscriptionCommand $command): void
    {
        $carId = new CarId(value: $command->carId);

        $carDto = $this->carApi->lockAndValidateCar(carId: $command->carId);

        $overlapping = $this->subscriptionRepository->findOverlappingSubscriptions(
            carId: $carId,
            startDate: $command->startDate,
            endDate: $command->endDate
        );

        if ([] !== $overlapping) {
            throw new DomainException(message: 'Car is already booked/unavailable.');
        }

        $subscription = new Subscription(
            id: SubscriptionId::generate(),
            userId: new UserId(value: $command->userId),
            carId: $carId,
            startDate: $command->startDate,
            endDate: $command->endDate,
            status: SubscriptionStatus::PENDING_PAYMENT
        );

        $this->subscriptionRepository->save(subscription: $subscription);
    }
}
