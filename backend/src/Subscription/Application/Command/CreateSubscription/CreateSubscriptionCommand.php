<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateSubscription;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommandHandler;
use DateTimeImmutable;

/**
 * @see CreateSubscriptionCommandHandler
 */
class CreateSubscriptionCommand
{
    public function __construct(
        public readonly string $userId,
        public readonly string $carId,
        public readonly DateTimeImmutable $startDate,
        public readonly ?DateTimeImmutable $endDate = null
    ) {
    }
}
