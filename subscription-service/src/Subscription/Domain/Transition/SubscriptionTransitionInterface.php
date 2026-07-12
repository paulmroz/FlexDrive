<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Transition;

use App\Subscription\Domain\Entity\Subscription;
use Closure;

/**
 * @psalm-immutable
 */
interface SubscriptionTransitionInterface
{
    public function canApply(Subscription $subscription): bool;

    /**
     * @param Closure(SubscriptionStatus $newStatus, ?\DateTimeImmutable $endDate): void $mutator
     */
    public function apply(Subscription $subscription, Closure $mutator): void;
}
