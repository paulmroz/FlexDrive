<?php

declare(strict_types=1);

namespace App\Car\Application\Command\UpdateCar;

use App\Car\Application\Command\UpdateCar\UpdateCarCommandHandler;

/**
 * @see UpdateCarCommandHandler
 */
class UpdateCarCommand
{
    public function __construct(
        public readonly string $id,
        public readonly string $brand,
        public readonly string $model,
        public readonly int $pricePerDay,
        public readonly bool $available
    ) {
    }
}
