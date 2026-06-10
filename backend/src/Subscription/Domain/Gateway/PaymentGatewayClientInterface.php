<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Gateway;

use App\Subscription\Domain\Gateway\StripeCheckoutSessionDto;

interface PaymentGatewayClientInterface
{
    public function refund(string $transactionId): void;

    public function createCheckoutSession(
        string $subscriptionId,
        string $carBrandAndModel,
        int $amount,
        string $currency
    ): StripeCheckoutSessionDto;
}
