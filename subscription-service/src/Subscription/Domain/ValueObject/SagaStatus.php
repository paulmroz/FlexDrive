<?php

declare(strict_types=1);

namespace App\Subscription\Domain\ValueObject;

enum SagaStatus: string
{
    case PENDING_LOCK = 'pending_lock';
    case LOCKED = 'locked';
    case COMPENSATION_REQUIRED = 'compensation_required';
    case COMPLETED = 'completed';
}
