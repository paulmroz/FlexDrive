<?php

declare(strict_types=1);

namespace App\AuditLog\Domain\Repository;

use App\AuditLog\Domain\Entity\AuditLog;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void;

    public function findById(string $id): ?AuditLog;

    /**
     * @return array<AuditLog>
     */
    public function findByAggregate(string $aggregateType, string $aggregateId): array;
}
