<?php

declare(strict_types=1);

namespace App\Car\Application\Query\GetCarDetails;

use App\Car\Application\Query\ListCars\CarReadModel;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;

#[AsMessageHandler]
class GetCarDetailsQueryHandler
{
    public function __construct(
        private readonly CarRepositoryInterface $carRepository
    ) {
    }

    public function __invoke(GetCarDetailsQuery $query): CarReadModel
    {
        $car = $this->carRepository->findById(id: new CarId(value: $query->id));

        if (null === $car) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        return new CarReadModel(
            id: $car->getId()->getValue(),
            brand: $car->getBrand(),
            model: $car->getModel(),
            pricePerDay: $car->getPricePerDay(),
            available: $car->isAvailable()
        );
    }
}
