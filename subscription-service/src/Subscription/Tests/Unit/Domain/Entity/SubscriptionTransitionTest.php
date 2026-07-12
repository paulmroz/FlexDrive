<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Unit\Domain\Entity;

use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\UserId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Transition\ActivateTransition;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SubscriptionTransitionTest extends TestCase
{
    public function testApplyTransitionCorrectlyMutatesStatus(): void
    {
        $subscription = new Subscription(
            id: SubscriptionId::generate(),
            userId: UserId::generate(),
            carId: CarId::generate(),
            startDate: new DateTimeImmutable(),
            endDate: null,
            status: SubscriptionStatus::PENDING_PAYMENT
        );

        $subscription->applyTransition(transition: new ActivateTransition());

        $this->assertSame(SubscriptionStatus::ACTIVE, $subscription->getStatus());
    }
}
