<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateSubscription;

use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Doctrine\DBAL\LockMode;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;
use DomainException;

/**
 * @see CreateSubscriptionCommand
 */
#[AsMessageHandler]
class CreateSubscriptionCommandHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly CarRepositoryInterface $carRepository
    ) {
    }

    public function __invoke(CreateSubscriptionCommand $command): void
    {
        $carId = new CarId(value: $command->carId);

        $car = $this->carRepository->findById(id: $carId, lockMode: LockMode::PESSIMISTIC_WRITE);

        if (null === $car) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        if (false === $car->isAvailable()) {
            throw new DomainException(message: 'Car is already booked/unavailable.');
        }

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
        $this->carRepository->save(car: $car);
    }
}
