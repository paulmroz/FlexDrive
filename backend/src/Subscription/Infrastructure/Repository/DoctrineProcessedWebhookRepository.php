<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Repository;

use App\Subscription\Domain\Entity\ProcessedWebhook;
use App\Subscription\Domain\Repository\ProcessedWebhookRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProcessedWebhook>
 */
class DoctrineProcessedWebhookRepository extends ServiceEntityRepository implements ProcessedWebhookRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(registry: $registry, entityClass: ProcessedWebhook::class);
    }

    public function save(ProcessedWebhook $processedWebhook): void
    {
        $this->getEntityManager()->persist($processedWebhook);
        $this->getEntityManager()->flush();
    }

    public function findByEventId(string $eventId): ?ProcessedWebhook
    {
        return $this->findOneBy(criteria: ['eventId' => $eventId]);
    }
}
