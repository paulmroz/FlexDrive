<?php

declare(strict_types=1);

namespace App\Car\Application\Query\GetCarsBatch;

use App\Car\Application\Query\ListCars\CarReadModel;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetCarsBatchQueryHandler
{
    public function __construct(
        private readonly CarRepositoryInterface $carRepository
    ) {
    }

    /**
     * @return CarReadModel[]
     */
    public function __invoke(GetCarsBatchQuery $query): array
    {
        if (empty($query->ids)) {
            return [];
        }

        $carIdsVO = array_map(
            callback: fn(string $id): CarId => new CarId(value: $id),
            array: $query->ids
        );

        $cars = $this->carRepository->findByIds(ids: $carIdsVO);
        $result = [];

        foreach ($cars as $car) {
            $result[] = new CarReadModel(
                id: $car->getId()->getValue(),
                brand: $car->getBrand(),
                model: $car->getModel(),
                pricePerDay: $car->getPricePerDay(),
                available: $car->isAvailable()
            );
        }

        return $result;
    }
}
