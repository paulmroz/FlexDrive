<?php

declare(strict_types=1);

namespace App\Car\Application\Query\GetCarsBatch;

class GetCarsBatchQuery
{
    /**
     * @param string[] $ids
     */
    public function __construct(
        public readonly array $ids
    ) {
    }
}
