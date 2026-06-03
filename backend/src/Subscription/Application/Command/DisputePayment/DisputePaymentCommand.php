<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\DisputePayment;

class DisputePaymentCommand
{
    public function __construct(
        public readonly string $webhookEventId,
        public readonly ?string $paymentId = null,
        public readonly ?string $sessionId = null
    ) {
    }
}
