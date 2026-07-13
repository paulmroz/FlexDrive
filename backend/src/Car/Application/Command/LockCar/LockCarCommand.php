<?php

declare(strict_types=1);

namespace App\Car\Application\Command\LockCar;

class LockCarCommand
{
    public function __construct(
        public readonly string $id
    ) {
    }
}
