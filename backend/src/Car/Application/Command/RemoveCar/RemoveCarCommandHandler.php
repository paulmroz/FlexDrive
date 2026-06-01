<?php

declare(strict_types=1);

namespace App\Car\Application\Command\RemoveCar;

use App\Car\Application\Command\RemoveCar\RemoveCarCommand;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;

/**
 * @see RemoveCarCommand
 */
#[AsMessageHandler]
class RemoveCarCommandHandler
{
    public function __construct(private readonly CarRepositoryInterface $carRepository)
    {
    }

    public function __invoke(RemoveCarCommand $command): void
    {
        $carId = new CarId(value: $command->id);
        $car = $this->carRepository->findById(id: $carId);

        if (null === $car) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        $this->carRepository->remove(car: $car);
    }
}
