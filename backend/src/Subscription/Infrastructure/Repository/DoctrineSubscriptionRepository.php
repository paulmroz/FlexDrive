<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Repository;

use App\Car\Domain\ValueObject\CarId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;

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

    public function findOverlappingSubscriptions(
        CarId $carId,
        DateTimeImmutable $startDate,
        ?DateTimeImmutable $endDate
    ): array {
        $qb = $this->createQueryBuilder(alias: 's');
        $startDateMinus3Hours = $startDate->modify(modifier: '-3 hours');
        $endDatePlus3Hours = null !== $endDate ? $endDate->modify(modifier: '+3 hours') : null;

        $qb->where('s.carId = :carId')
            ->andWhere('s.status IN (:activeStatuses)')
            ->andWhere('(s.endDate IS NULL OR s.endDate > :startDateMinus3Hours)');

        if (null !== $endDatePlus3Hours) {
            $qb->andWhere('s.startDate < :endDatePlus3Hours');
            $qb->setParameter(key: 'endDatePlus3Hours', value: $endDatePlus3Hours);
        }

        return $qb->setParameter(key: 'carId', value: $carId->getValue())
            ->setParameter(key: 'activeStatuses', value: [
                SubscriptionStatus::PENDING_PAYMENT,
                SubscriptionStatus::ACTIVE,
            ])
            ->setParameter(key: 'startDateMinus3Hours', value: $startDateMinus3Hours)
            ->getQuery()
            ->getResult();
    }
}
