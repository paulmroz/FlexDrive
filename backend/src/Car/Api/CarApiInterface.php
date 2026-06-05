<?php

declare(strict_types=1);

namespace App\Car\Api;

use App\Car\Api\Dto\CarDto;

interface CarApiInterface
{
    public function lockAndValidateCar(string $carId): CarDto;
}
