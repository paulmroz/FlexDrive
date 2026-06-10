<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\ConfirmPayment;

use App\Subscription\Domain\Entity\ProcessedWebhook;
use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\Repository\PaymentRepositoryInterface;
use App\Subscription\Domain\Repository\ProcessedWebhookRepositoryInterface;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use App\Subscription\Domain\Transition\ActivateTransition;
use App\Subscription\Domain\Transition\ReactivateTransition;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use DateTimeImmutable;
use InvalidArgumentException;

#[AsMessageHandler]
class ConfirmPaymentCommandHandler
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ProcessedWebhookRepositoryInterface $processedWebhookRepository,
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly PaymentGatewayClientInterface $paymentGatewayClient
    ) {
    }

    public function __invoke(ConfirmPaymentCommand $command): void
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

        $payment = $this->paymentRepository->findBySessionIdWithWriteLock(sessionId: $command->sessionId);

        if (null === $payment) {
            throw new InvalidArgumentException(message: 'Payment session not found.');
        }

        $currentStatus = $payment->getStatus();
        if (PaymentStatus::PAID === $currentStatus || PaymentStatus::REFUNDED === $currentStatus || PaymentStatus::DISPUTED === $currentStatus || PaymentStatus::PAID_CONFLICT === $currentStatus) {
            return;
        }

        if ($command->amount !== $payment->getAmount() || strtoupper(string: $command->currency) !== strtoupper(string: $payment->getCurrency())) {
            $payment->markAsConflict();
            $this->paymentRepository->save(payment: $payment);
            $this->paymentGatewayClient->refund(transactionId: $command->transactionId);
            return;
        }

        $subscription = $this->subscriptionRepository->findById(
            id: new SubscriptionId(value: $payment->getSubscriptionId())
        );

        if (null === $subscription) {
            throw new InvalidArgumentException(message: 'Subscription not found.');
        }

        if (SubscriptionStatus::PENDING_PAYMENT === $subscription->getStatus()) {
            $payment->markAsPaid(paidAt: new DateTimeImmutable());
            $subscription->applyTransition(transition: new ActivateTransition());

            $this->paymentRepository->save(payment: $payment);
            $this->subscriptionRepository->save(subscription: $subscription);
        } elseif (SubscriptionStatus::CANCELLED === $subscription->getStatus()) {
            $overlapping = $this->subscriptionRepository->findOverlappingSubscriptions(
                carId: $subscription->getCarId(),
                startDate: $subscription->getStartDate(),
                endDate: $subscription->getEndDate()
            );

            if ([] === $overlapping) {
                $payment->markAsPaid(paidAt: new DateTimeImmutable());
                $subscription->applyTransition(transition: new ReactivateTransition());

                $this->paymentRepository->save(payment: $payment);
                $this->subscriptionRepository->save(subscription: $subscription);
            } else {
                $payment->markAsConflict();
                $this->paymentRepository->save(payment: $payment);
                $this->paymentGatewayClient->refund(transactionId: $command->transactionId);
            }
        }
    }
}
