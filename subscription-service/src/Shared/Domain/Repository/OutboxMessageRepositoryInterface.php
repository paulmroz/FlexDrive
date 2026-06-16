<?php

declare(strict_types=1);

namespace App\Shared\Domain\Repository;

use App\Shared\Domain\Entity\OutboxMessage;

interface OutboxMessageRepositoryInterface
{
    public function save(OutboxMessage $message): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAndLockNextMessages(int $limit): array;

    public function markAsProcessed(string $id): void;

    public function incrementRetry(string $id, string $error): void;
}
