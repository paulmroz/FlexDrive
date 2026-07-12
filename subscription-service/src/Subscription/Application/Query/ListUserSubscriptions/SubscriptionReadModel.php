<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\ListUserSubscriptions;

class SubscriptionReadModel
{
    public function __construct(
        public readonly string $id,
        public readonly string $carId,
        public readonly string $carBrand,
        public readonly string $carModel,
        public readonly string $status,
        public readonly string $startDate,
        public readonly ?string $endDate,
        public readonly string $createdAt
    ) {
    }
}
