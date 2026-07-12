<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Car\Domain\ValueObject\CarId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use DateTimeImmutable;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void;

    public function findById(SubscriptionId $id): ?Subscription;

    public function findOverlappingSubscriptions(
        CarId $carId,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate
    ): array;

    /**
     * @return array<Subscription>
     */
    public function findByUserId(string $userId): array;
}
