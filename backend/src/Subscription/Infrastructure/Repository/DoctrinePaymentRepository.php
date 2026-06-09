<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Repository;

use App\Subscription\Domain\Entity\Payment;
use App\Subscription\Domain\Repository\PaymentRepositoryInterface;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class DoctrinePaymentRepository extends ServiceEntityRepository implements PaymentRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(registry: $registry, entityClass: Payment::class);
    }

    public function save(Payment $payment): void
    {
        $this->getEntityManager()->persist($payment);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?Payment
    {
        return $this->find(id: $id);
    }

    public function findByIdWithWriteLock(string $id): ?Payment
    {
        return $this->find(id: $id, lockMode: LockMode::PESSIMISTIC_WRITE);
    }

    public function findBySessionId(string $sessionId): ?Payment
    {
        return $this->createQueryBuilder(alias: 'p')
            ->where('p.sessionId = :sessionId')
            ->setParameter(key: 'sessionId', value: $sessionId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySessionIdWithWriteLock(string $sessionId): ?Payment
    {
        $query = $this->createQueryBuilder(alias: 'p')
            ->where('p.sessionId = :sessionId')
            ->setParameter(key: 'sessionId', value: $sessionId)
            ->getQuery();

        $query->setLockMode(lockMode: LockMode::PESSIMISTIC_WRITE);

        return $query->getOneOrNullResult();
    }

    public function findCreatedOlderThan(DateTimeImmutable $threshold): array
    {
        $qb = $this->createQueryBuilder(alias: 'p');

        return $qb->where('p.status = :status')
            ->andWhere('p.createdAt < :threshold')
            ->setParameter(key: 'status', value: PaymentStatus::CREATED)
            ->setParameter(key: 'threshold', value: $threshold)
            ->getQuery()
            ->getResult();
    }
}
