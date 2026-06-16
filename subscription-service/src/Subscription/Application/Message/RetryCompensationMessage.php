<?php

declare(strict_types=1);

namespace App\Subscription\Application\Message;

class RetryCompensationMessage
{
    public function __construct(
        public readonly string $carId,
        public readonly string $sagaId
    ) {
    }
}
