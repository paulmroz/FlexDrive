<?php

declare(strict_types=1);

namespace App\Car\Domain\Repository;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;

interface CarRepositoryInterface
{
    public function save(Car $car): void;

    public function remove(Car $car): void;

    public function findById(CarId $id): ?Car;

    public function findByIdWithWriteLock(CarId $id): ?Car;

    /**
     * @param CarId[] $ids
     * @return Car[]
     */
    public function findByIds(array $ids): array;

    /**
     * @return array<Car>
     */
    public function findAllCars(): array;

    /**
     * @return array<Car>
     */
    public function findAvailableCars(): array;

    /**
     * @return array<Car>
     */
    public function findAvailableCarsByCriteria(?string $brand = null, ?string $model = null, ?int $maxPricePerDay = null, ?int $minPricePerDay = null): array;

    /**
     * @return array<Car>
     */
    public function findFallbackCars(?int $maxPricePerDay = null, int $limit = 5): array;
}

