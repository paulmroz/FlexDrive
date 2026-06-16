<?php

declare(strict_types=1);

namespace App\Shared\Tests\Integration\Repository;

use App\Shared\Domain\Entity\OutboxMessage;
use App\Shared\Infrastructure\Repository\OutboxMessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OutboxMessageRepositoryConcurrencyTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private OutboxMessageRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = static::getContainer()->get(id: EntityManagerInterface::class);
        $this->repository = static::getContainer()->get(id: OutboxMessageRepository::class);

        // Clean up table
        $this->entityManager->getConnection()->executeStatement(sql: 'DELETE FROM outbox_messages');
    }

    public function testSkipLockedPreventsConcurrentProcessingOfSameMessages(): void
    {
        // 1. Insert 10 outbox messages
        for ($i = 0; $i < 10; $i++) {
            $message = new OutboxMessage(type: 'TestMessage', payload: '{"idx":' . $i . '}');
            $this->repository->save(message: $message);
        }

        // 2. Open two separate connections to simulate two workers
        $connection1 = $this->entityManager->getConnection();
        
        // We need a completely separate connection from the pool to simulate concurrency
        $params = $connection1->getParams();
        $connection2 = \Doctrine\DBAL\DriverManager::getConnection(params: $params);

        // 3. Worker 1 starts a transaction and locks 5 messages
        $connection1->beginTransaction();
        
        $sql = 'SELECT * FROM outbox_messages WHERE processed_at IS NULL ORDER BY created_at ASC LIMIT 5 FOR UPDATE SKIP LOCKED';
        $messages1 = $connection1->fetchAllAssociative(query: $sql);
        
        $this->assertCount(expectedCount: 5, haystack: $messages1);

        // 4. Worker 2 starts a transaction and tries to lock 5 messages
        $connection2->beginTransaction();
        $messages2 = $connection2->fetchAllAssociative(query: $sql);

        // Worker 2 should get the REMAINING 5 messages, not the ones locked by Worker 1
        $this->assertCount(expectedCount: 5, haystack: $messages2);

        // Verify there is no overlap
        $ids1 = array_column(array: $messages1, column_key: 'id');
        $ids2 = array_column(array: $messages2, column_key: 'id');

        $overlap = array_intersect($ids1, $ids2);
        $this->assertEmpty(actual: $overlap, message: 'SKIP LOCKED failed: Workers fetched overlapping messages.');

        // Clean up transactions
        $connection1->rollBack();
        $connection2->rollBack();
        $connection2->close();
    }
}
