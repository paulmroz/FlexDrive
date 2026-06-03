<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Event;

class PaymentCompletedEvent
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $subscriptionId
    ) {
    }
}
