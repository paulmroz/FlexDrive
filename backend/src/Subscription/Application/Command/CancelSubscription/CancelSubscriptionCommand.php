<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CancelSubscription;

use App\Subscription\Application\Command\CancelSubscription\CancelSubscriptionCommandHandler;

/**
 * @see CancelSubscriptionCommandHandler
 */
class CancelSubscriptionCommand
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $userId
    ) {
    }
}
