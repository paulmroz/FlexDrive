<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\ConfirmPayment;

use App\Subscription\Application\Command\ConfirmPayment\ConfirmPaymentCommandHandler;

/**
 * @see ConfirmPaymentCommandHandler
 */
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
