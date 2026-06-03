<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\ConfirmPayment;

class ConfirmPaymentCommand
{
    public function __construct(
        public readonly string $webhookEventId,
        public readonly string $sessionId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $transactionId
    ) {
    }
}
