<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\ListUserSubscriptions;

use App\Car\Api\CarApiInterface;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Application\Query\ListUserSubscriptions\ListUserSubscriptionsQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Throwable;

/**
 * @see ListUserSubscriptionsQuery
 */
#[AsMessageHandler]
class ListUserSubscriptionsQueryHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly CarApiInterface $carApi
    ) {
    }

    /**
     * @return array<array{id: string, carId: string, carBrand: string, carModel: string, status: string, startDate: string, endDate: ?string, createdAt: string}>
     */
    public function __invoke(ListUserSubscriptionsQuery $query): array
    {
        $subscriptions = $this->subscriptionRepository->findByUserId(userId: $query->userId);
        $result = [];

        foreach ($subscriptions as $subscription) {
            $carId = $subscription->getCarId()->getValue();
            $carBrand = 'Unknown';
            $carModel = 'Car';

            try {
                $carDto = $this->carApi->getCarDetails(carId: $carId);
                $carBrand = $carDto->brand;
                $carModel = $carDto->model;
            } catch (Throwable) {
                // Keep defaults if car not found
            }

            $result[] = [
                'id' => $subscription->getId()->getValue(),
                'carId' => $carId,
                'carBrand' => $carBrand,
                'carModel' => $carModel,
                'status' => $subscription->getStatus()->value,
                'startDate' => $subscription->getStartDate()->format(format: 'Y-m-d H:i:s'),
                'endDate' => null !== $subscription->getEndDate() ? $subscription->getEndDate()->format(format: 'Y-m-d H:i:s') : null,
                'createdAt' => $subscription->getCreatedAt()->format(format: 'Y-m-d H:i:s'),
            ];
        }

        return $result;
    }
}
