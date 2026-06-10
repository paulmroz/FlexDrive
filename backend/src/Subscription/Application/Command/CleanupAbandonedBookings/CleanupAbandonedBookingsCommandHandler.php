<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CleanupAbandonedBookings;

use App\Subscription\Domain\Repository\PaymentRepositoryInterface;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use App\Subscription\Domain\Transition\FailTransition;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use DateTimeImmutable;

#[AsMessageHandler]
class CleanupAbandonedBookingsCommandHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly string $cleanupThreshold
    ) {
    }

    public function __invoke(CleanupAbandonedBookingsCommand $command): void
    {
        $threshold = new DateTimeImmutable(datetime: $this->cleanupThreshold);
        $abandonedPayments = $this->paymentRepository->findCreatedOlderThan(threshold: $threshold);

        foreach ($abandonedPayments as $payment) {
            $payment->markAsFailed();
            $this->paymentRepository->save(payment: $payment);

            $subscription = $this->subscriptionRepository->findById(
                id: new SubscriptionId(value: $payment->getSubscriptionId())
            );

            if (null !== $subscription && SubscriptionStatus::PENDING_PAYMENT === $subscription->getStatus()) {
                $subscription->applyTransition(transition: new FailTransition(failedAt: new DateTimeImmutable()));
                $this->subscriptionRepository->save(subscription: $subscription);
            }
        }
    }
}
