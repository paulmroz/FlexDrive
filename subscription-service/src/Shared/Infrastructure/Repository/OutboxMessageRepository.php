<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Repository;

use App\Shared\Domain\Entity\OutboxMessage;
use App\Shared\Domain\Repository\OutboxMessageRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Throwable;

class OutboxMessageRepository extends ServiceEntityRepository implements OutboxMessageRepositoryInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct(registry: $registry, entityClass: OutboxMessage::class);
    }

    public function save(OutboxMessage $message): void
    {
        $this->getEntityManager()->persist(object: $message);
        $this->getEntityManager()->flush();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAndLockNextMessages(int $limit): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();

        try {
            $sql = 'SELECT * FROM outbox_messages 
                    WHERE processed_at IS NULL 
                    ORDER BY created_at ASC 
                    LIMIT :limit 
                    FOR UPDATE SKIP LOCKED';

            $messages = $conn->fetchAllAssociative(
                query: $sql,
                params: ['limit' => $limit]
            );

            $conn->commit();
            return $messages;
        } catch (Throwable $e) {
            $conn->rollBack();
            $this->logger->error(message: 'Failed to lock outbox messages: ' . $e->getMessage());
            throw $e;
        }
    }

    public function markAsProcessed(string $id): void
    {
        $sql = 'UPDATE outbox_messages SET processed_at = NOW() WHERE id = :id';
        $this->getEntityManager()->getConnection()->executeStatement(
            sql: $sql,
            params: ['id' => $id]
        );
    }

    public function incrementRetry(string $id, string $error): void
    {
        $sql = 'UPDATE outbox_messages SET retry_count = retry_count + 1, last_error = :error WHERE id = :id';
        $this->getEntityManager()->getConnection()->executeStatement(
            sql: $sql,
            params: ['error' => $error, 'id' => $id]
        );
    }
}
