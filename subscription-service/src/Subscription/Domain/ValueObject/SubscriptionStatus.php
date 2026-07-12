<?php

declare(strict_types=1);

namespace App\Subscription\Domain\ValueObject;

enum SubscriptionStatus: string
{
    case PENDING_PAYMENT = 'pending_payment';
    case ACTIVE = 'active';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
}
