<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Gateway;

class StripeCheckoutSessionDto
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $url
    ) {
    }
}
