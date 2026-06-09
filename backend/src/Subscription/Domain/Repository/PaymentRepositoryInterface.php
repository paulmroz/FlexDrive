<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\Payment;
use DateTimeImmutable;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;

    public function findById(string $id): ?Payment;

    public function findByIdWithWriteLock(string $id): ?Payment;

    public function findBySessionId(string $sessionId): ?Payment;

    public function findBySessionIdWithWriteLock(string $sessionId): ?Payment;

    /**
     * @return array<Payment>
     */
    public function findCreatedOlderThan(DateTimeImmutable $threshold): array;
}
