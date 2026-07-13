<?php

declare(strict_types=1);

namespace App\Car\Api\Dto;

class CarDto
{
    public function __construct(
        public readonly string $id,
        public readonly string $brand,
        public readonly string $model,
        public readonly int $pricePerDay
    ) {
    }
}
