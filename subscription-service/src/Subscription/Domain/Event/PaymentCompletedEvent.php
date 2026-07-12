<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Event;

use App\Shared\Domain\Event\AuditableEventInterface;

class PaymentCompletedEvent implements AuditableEventInterface
{
    public function __construct(
        public readonly string $paymentId,
        public readonly string $subscriptionId
    ) {
    }

    public function getEventType(): string
    {
        return 'subscription.payment_completed';
    }

    public function getAggregateId(): string
    {
        return $this->paymentId;
    }

    public function getAggregateType(): string
    {
        return 'Payment';
    }

    /**
     * @return array<string, mixed>
     */
    public function getAuditPayload(): array
    {
        return [
            'subscriptionId' => $this->subscriptionId,
        ];
    }
}
