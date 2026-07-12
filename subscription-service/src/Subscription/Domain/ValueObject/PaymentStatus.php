<?php

declare(strict_types=1);

namespace App\Subscription\Domain\ValueObject;

enum PaymentStatus: string
{
    case CREATED = 'created';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case DISPUTED = 'disputed';
    case PAID_CONFLICT = 'paid_conflict';
}
