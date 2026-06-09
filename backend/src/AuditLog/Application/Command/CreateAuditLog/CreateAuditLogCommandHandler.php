<?php

declare(strict_types=1);

namespace App\AuditLog\Application\Command\CreateAuditLog;

use App\AuditLog\Domain\Entity\AuditLog;
use App\AuditLog\Domain\Repository\AuditLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateAuditLogCommandHandler
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository
    ) {
    }

    public function __invoke(CreateAuditLogCommand $command): void
    {
        $auditLog = new AuditLog(
            id: Uuid::v4()->toString(),
            eventType: $command->eventType,
            aggregateId: $command->aggregateId,
            aggregateType: $command->aggregateType,
            payload: $command->payload,
            userId: $command->userId,
            occurredAt: $command->occurredAt
        );

        $this->auditLogRepository->save(auditLog: $auditLog);
    }
}
