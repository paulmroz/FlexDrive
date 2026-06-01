<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Repository;

use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subscription>
 */
class DoctrineSubscriptionRepository extends ServiceEntityRepository implements SubscriptionRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(registry: $registry, entityClass: Subscription::class);
    }

    public function save(Subscription $subscription): void
    {
        $this->getEntityManager()->persist($subscription);
        $this->getEntityManager()->flush();
    }

    public function findById(SubscriptionId $id): ?Subscription
    {
        return $this->find(id: $id->getValue());
    }
}
