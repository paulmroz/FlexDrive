<?php

declare(strict_types=1);

namespace App\Car\Domain\Repository;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;

interface CarRepositoryInterface
{
    public function save(Car $car): void;

    public function remove(Car $car): void;

    public function findById(CarId $id, ?int $lockMode = null): ?Car;

    /**
     * @return array<Car>
     */
    public function findAllCars(): array;

    /**
     * @return array<Car>
     */
    public function findAvailableCars(): array;
}

