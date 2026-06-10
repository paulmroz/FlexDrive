<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Unit\Domain\Transition;

use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Exception\IllegalTransitionException;
use App\Subscription\Domain\Transition\ActivateTransition;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ActivateTransitionTest extends TestCase
{
    private function createSubscription(SubscriptionStatus $status): Subscription
    {
        return new Subscription(
            id: new SubscriptionId(value: 'sub-1'),
            userId: new UserId(value: 'user-1'),
            carId: new CarId(value: 'car-1'),
            startDate: new DateTimeImmutable(),
            endDate: null,
            status: $status
        );
    }

    public function testCanApplyReturnsTrueForPendingPayment(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatus::PENDING_PAYMENT);
        $transition = new ActivateTransition();

        $this->assertTrue($transition->canApply(subscription: $subscription));
    }

    public function testApplyIsIdempotent(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatus::ACTIVE);
        $transition = new ActivateTransition();

        $mutatorCalled = false;
        $mutator = function() use (&$mutatorCalled) {
            $mutatorCalled = true;
        };

        $transition->apply(subscription: $subscription, mutator: $mutator);

        $this->assertFalse($mutatorCalled, 'Mutator should not be called if already active.');
    }

    public function testApplyThrowsIfStatusIsCancelled(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatus::CANCELLED);
        $transition = new ActivateTransition();

        $this->expectException(IllegalTransitionException::class);

        $transition->apply(subscription: $subscription, mutator: function() {});
    }

    public function testApplyCallsMutatorCorrectly(): void
    {
        $subscription = $this->createSubscription(status: SubscriptionStatus::PENDING_PAYMENT);
        $transition = new ActivateTransition();

        $capturedStatus = null;
        $capturedEndDate = false;
        $mutator = function($status, $endDate) use (&$capturedStatus, &$capturedEndDate) {
            $capturedStatus = $status;
            $capturedEndDate = $endDate;
        };

        $transition->apply(subscription: $subscription, mutator: $mutator);

        $this->assertSame(SubscriptionStatus::ACTIVE, $capturedStatus);
        $this->assertNull($capturedEndDate);
    }
}
