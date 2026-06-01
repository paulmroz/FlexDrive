<?php

declare(strict_types=1);

namespace App\Car\Application\Command\AddCar;

use App\Car\Application\Command\AddCar\AddCarCommandHandler;

/**
 * @see AddCarCommandHandler
 */
class AddCarCommand
{
    public function __construct(
        public readonly string $brand,
        public readonly string $model,
        public readonly int $pricePerDay
    ) {
    }
}
