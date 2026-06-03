<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\RefundPayment;

use App\Subscription\Domain\Entity\ProcessedWebhook;
use App\Subscription\Domain\Repository\PaymentRepositoryInterface;
use App\Subscription\Domain\Repository\ProcessedWebhookRepositoryInterface;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use DateTimeImmutable;

#[AsMessageHandler]
class RefundPaymentCommandHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ProcessedWebhookRepositoryInterface $processedWebhookRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public function __invoke(RefundPaymentCommand $command): void
    {
        $existing = $this->processedWebhookRepository->findByEventId(eventId: $command->webhookEventId);
        if (null !== $existing) {
            return;
        }

        try {
            $processedWebhook = new ProcessedWebhook(
                id: Uuid::v4()->toString(),
                eventId: $command->webhookEventId
            );
            $this->processedWebhookRepository->save(processedWebhook: $processedWebhook);
        } catch (UniqueConstraintViolationException) {
            return;
        }

        $payment = null;

        if (null !== $command->paymentId) {
            $payment = $this->paymentRepository->findById(
                id: $command->paymentId,
                lockMode: LockMode::PESSIMISTIC_WRITE
            );
        } elseif (null !== $command->sessionId) {
            $payment = $this->paymentRepository->findBySessionId(
                sessionId: $command->sessionId,
                lockMode: LockMode::PESSIMISTIC_WRITE
            );
        }

        if (null === $payment) {
            return;
        }

        if (PaymentStatus::REFUNDED === $payment->getStatus()) {
            return;
        }

        $payment->markAsRefunded();
        $this->paymentRepository->save(payment: $payment);

        $subscription = $this->subscriptionRepository->findById(
            id: new SubscriptionId(value: $payment->getSubscriptionId())
        );

        if (null !== $subscription && SubscriptionStatus::CANCELLED !== $subscription->getStatus()) {
            $subscription->cancel(cancelledAt: new DateTimeImmutable());
            $this->subscriptionRepository->save(subscription: $subscription);
        }
    }
}
