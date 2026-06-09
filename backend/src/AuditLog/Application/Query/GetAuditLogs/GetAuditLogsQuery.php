<?php

declare(strict_types=1);

namespace App\AuditLog\Application\Query\GetAuditLogs;

/**
 * @see GetAuditLogsQueryHandler
 */
class GetAuditLogsQuery
{
    public function __construct(
        public readonly string $aggregateType,
        public readonly string $aggregateId
    ) {
    }
}
