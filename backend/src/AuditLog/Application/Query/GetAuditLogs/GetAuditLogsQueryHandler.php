<?php

declare(strict_types=1);

namespace App\AuditLog\Application\Query\GetAuditLogs;

use App\AuditLog\Domain\Repository\AuditLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetAuditLogsQueryHandler
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $auditLogRepository
    ) {
    }

    /**
     * @return AuditLogReadModel[]
     */
    public function __invoke(GetAuditLogsQuery $query): array
    {
        $logs = $this->auditLogRepository->findByAggregate(
            aggregateType: $query->aggregateType,
            aggregateId: $query->aggregateId
        );

        $result = [];
        foreach ($logs as $log) {
            $result[] = new AuditLogReadModel(
                id: $log->getId(),
                eventType: $log->getEventType(),
                aggregateId: $log->getAggregateId(),
                aggregateType: $log->getAggregateType(),
                payload: $log->getPayload(),
                userId: $log->getUserId(),
                occurredAt: $log->getOccurredAt()->format(format: 'Y-m-d H:i:s')
            );
        }

        return $result;
    }
}
