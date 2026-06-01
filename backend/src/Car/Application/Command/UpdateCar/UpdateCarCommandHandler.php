<?php

declare(strict_types=1);

namespace App\Car\Application\Command\UpdateCar;

use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;

#[AsMessageHandler]
class UpdateCarCommandHandler
{
    public function __construct(private readonly CarRepositoryInterface $carRepository)
    {
    }

    public function __invoke(UpdateCarCommand $command): void
    {
        $carId = new CarId(value: $command->id);
        $car = $this->carRepository->findById(id: $carId);

        if (null === $car) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        $car->update(
            brand: $command->brand,
            model: $command->model,
            pricePerDay: $command->pricePerDay,
            available: $command->available
        );

        $this->carRepository->save(car: $car);
    }
}
