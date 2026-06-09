<?php

declare(strict_types=1);

namespace App\AuditLog\Infrastructure\Repository;

use App\AuditLog\Domain\Entity\AuditLog;
use App\AuditLog\Domain\Repository\AuditLogRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class DoctrineAuditLogRepository extends ServiceEntityRepository implements AuditLogRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(registry: $registry, entityClass: AuditLog::class);
    }

    public function save(AuditLog $auditLog): void
    {
        $this->getEntityManager()->persist(entity: $auditLog);
        $this->getEntityManager()->flush();
    }

    public function findById(string $id): ?AuditLog
    {
        return $this->find(id: $id);
    }

    /**
     * @return array<AuditLog>
     */
    public function findByAggregate(string $aggregateType, string $aggregateId): array
    {
        return $this->createQueryBuilder(alias: 'al')
            ->where('al.aggregateType = :aggregateType')
            ->andWhere('al.aggregateId = :aggregateId')
            ->setParameter(key: 'aggregateType', value: $aggregateType)
            ->setParameter(key: 'aggregateId', value: $aggregateId)
            ->orderBy(sort: 'al.occurredAt', order: 'DESC')
            ->getQuery()
            ->getResult();
    }
}
