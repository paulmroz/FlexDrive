<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Transition;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Exception\IllegalTransitionException;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Closure;
use DateTimeImmutable;

class CancelTransition implements SubscriptionTransitionInterface
{
    public function __construct(
        private readonly DateTimeImmutable $cancelledAt
    ) {
    }

    public function canApply(Subscription $subscription): bool
    {
        return SubscriptionStatus::CANCELLED !== $subscription->getStatus()
            && SubscriptionStatus::EXPIRED !== $subscription->getStatus();
    }

    public function apply(Subscription $subscription, Closure $mutator): void
    {
        if (SubscriptionStatus::CANCELLED === $subscription->getStatus()) {
            return;
        }

        if (!$this->canApply(subscription: $subscription)) {
            throw IllegalTransitionException::create(
                transitionName: self::class,
                currentStatus: $subscription->getStatus()->value
            );
        }

        $mutator(SubscriptionStatus::CANCELLED, $this->cancelledAt);
    }
}
