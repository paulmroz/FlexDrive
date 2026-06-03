<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Gateway;

interface PaymentGatewayClientInterface
{
    public function refund(string $transactionId): void;
}
