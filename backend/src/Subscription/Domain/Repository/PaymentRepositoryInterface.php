<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\Payment;
use DateTimeImmutable;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(string $id, ?int $lockMode = null): ?Payment;

    public function findBySessionId(string $sessionId, ?int $lockMode = null): ?Payment;

    /**
     * @return array<Payment>
     */
    public function findCreatedOlderThan(DateTimeImmutable $threshold): array;
}
