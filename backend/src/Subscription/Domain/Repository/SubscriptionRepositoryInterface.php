<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Repository;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\ValueObject\SubscriptionId;

interface SubscriptionRepositoryInterface
{
    public function save(Subscription $subscription): void;

    public function findById(SubscriptionId $id): ?Subscription;
}
