<?php

declare(strict_types=1);

namespace App\Car\Application\Command\LockCar;

use App\Car\Api\Dto\CarDto;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;
use DomainException;

#[AsMessageHandler]
class LockCarCommandHandler
{
    public function __construct(
        private readonly CarRepositoryInterface $carRepository
    ) {
    }

    public function __invoke(LockCarCommand $command): CarDto
    {
        $car = $this->carRepository->findByIdWithWriteLock(id: new CarId(value: $command->id));

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
