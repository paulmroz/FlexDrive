<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateCheckoutSession;

use App\Subscription\Application\Command\CreateCheckoutSession\CreateCheckoutSessionCommandHandler;

/**
 * @see CreateCheckoutSessionCommandHandler
 */
class CreateCheckoutSessionCommand
{
    public function __construct(
        public readonly string $subscriptionId,
        public readonly string $userId
    ) {
    }
}
