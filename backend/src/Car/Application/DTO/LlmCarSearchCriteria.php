<?php

declare(strict_types=1);

namespace App\Car\Application\DTO;

class LlmCarSearchCriteria
{
    public function __construct(
        public ?string $brand = null,
        public ?string $model = null,
        public ?int $maxPricePerDay = null,
        public ?int $minPricePerDay = null
    ) {
    }
}
