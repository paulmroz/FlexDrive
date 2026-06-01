<?php

declare(strict_types=1);

namespace App\Car\Application\Query\ListCars;

use App\Car\Application\Query\ListCars\ListCarsQuery;
use App\Car\Domain\Repository\CarRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @see ListCarsQuery
 */
#[AsMessageHandler]
class ListCarsQueryHandler
{
    public function __construct(private readonly CarRepositoryInterface $carRepository)
    {
    }

    /**
     * @return array<array{id: string, brand: string, model: string, pricePerDay: int, available: bool}>
     */
    public function __invoke(ListCarsQuery $query): array
    {
        $cars = $this->carRepository->findAllCars();
        $result = [];

        foreach ($cars as $car) {
            $result[] = [
                'id' => $car->getId()->getValue(),
                'brand' => $car->getBrand(),
                'model' => $car->getModel(),
                'pricePerDay' => $car->getPricePerDay(),
                'available' => $car->isAvailable(),
            ];
        }

        return $result;
    }
}
