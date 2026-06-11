<?php

declare(strict_types=1);

namespace App\AuditLog\Tests\Integration;

use App\AuditLog\Domain\Entity\AuditLog;
use App\AuditLog\Domain\Repository\AuditLogRepositoryInterface;
use App\AuditLog\Application\Command\CreateAuditLog\CreateAuditLogCommand;
use App\AuditLog\Application\Query\GetAuditLogs\GetAuditLogsQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;
use App\AuditLog\Application\Command\CreateAuditLog\CreateAuditLogCommandHandler;
use App\AuditLog\Application\Query\GetAuditLogs\GetAuditLogsQueryHandler;

class AuditLogIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private AuditLogRepositoryInterface $auditLogRepository;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $this->auditLogRepository = $container->get(id: AuditLogRepositoryInterface::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(sql: 'TRUNCATE TABLE audit_logs CASCADE');
    }

    public function testSaveAndRetrieveAuditLog(): void
    {
        $id = Uuid::v4()->toString();
        $occurredAt = new DateTimeImmutable();

        $auditLog = new AuditLog(
            id: $id,
            eventType: 'test.event',
            aggregateId: 'agg-123',
            aggregateType: 'TestAggregate',
            payload: ['foo' => 'bar'],
            userId: 'user-456',
            occurredAt: $occurredAt
        );

        $this->auditLogRepository->save(auditLog: $auditLog);

        $this->entityManager->clear();

        $retrieved = $this->auditLogRepository->findById(id: $id);
        $this->assertNotNull(actual: $retrieved);
        $this->assertSame(expected: $id, actual: $retrieved->getId());
        $this->assertSame(expected: 'test.event', actual: $retrieved->getEventType());
        $this->assertSame(expected: 'agg-123', actual: $retrieved->getAggregateId());
        $this->assertSame(expected: 'TestAggregate', actual: $retrieved->getAggregateType());
        $this->assertSame(expected: ['foo' => 'bar'], actual: $retrieved->getPayload());
        $this->assertSame(expected: 'user-456', actual: $retrieved->getUserId());
        $this->assertSame(
            expected: $occurredAt->format(format: 'Y-m-d H:i:s'),
            actual: $retrieved->getOccurredAt()->format(format: 'Y-m-d H:i:s')
        );
    }

    public function testCreateAuditLogCommandAndQueryFlow(): void
    {
        $occurredAt = new DateTimeImmutable();
        $command = new CreateAuditLogCommand(
            eventType: 'test.command',
            aggregateId: 'agg-789',
            aggregateType: 'CommandAggregate',
            payload: ['baz' => 'qux'],
            userId: 'user-999',
            occurredAt: $occurredAt
        );

        $handler = self::getContainer()->get(id: CreateAuditLogCommandHandler::class);
        $handler(command: $command);

        $this->entityManager->clear();

        $query = new GetAuditLogsQuery(
            aggregateType: 'CommandAggregate',
            aggregateId: 'agg-789'
        );

        $handlerQuery = self::getContainer()->get(id: GetAuditLogsQueryHandler::class);
        $results = $handlerQuery(query: $query);

        $this->assertCount(expectedCount: 1, haystack: $results);
        $retrieved = $results[0];
        $this->assertSame(expected: 'test.command', actual: $retrieved->eventType);
        $this->assertSame(expected: ['baz' => 'qux'], actual: $retrieved->payload);
        $this->assertSame(expected: 'user-999', actual: $retrieved->userId);
        $this->assertSame(
            expected: $occurredAt->format(format: 'Y-m-d H:i:s'),
            actual: $retrieved->occurredAt
        );
    }
}
