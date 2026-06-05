<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Api;

use App\Car\Api\CarApiInterface;
use App\Car\Api\Dto\CarDto;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Doctrine\DBAL\LockMode;
use InvalidArgumentException;
use DomainException;

class CarApi implements CarApiInterface
{
    public function __construct(
        private readonly CarRepositoryInterface $carRepository
    ) {
    }

    public function lockAndValidateCar(string $carId): CarDto
    {
        $car = $this->carRepository->findById(
            id: new CarId(value: $carId),
            lockMode: LockMode::PESSIMISTIC_WRITE
        );

        if (null === $car) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        if (false === $car->isAvailable()) {
            throw new DomainException(message: 'Car is already booked/unavailable.');
        }

        return new CarDto(
            id: $car->getId()->getValue(),
            brand: $car->getBrand(),
            model: $car->getModel(),
            pricePerDay: $car->getPricePerDay()
        );
    }
}
