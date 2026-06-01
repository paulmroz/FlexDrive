<?php

declare(strict_types=1);

namespace App\Car\Application\Command\AddCar;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @see AddCarCommand
 */
#[AsMessageHandler]
class AddCarCommandHandler
{
    public function __construct(private readonly CarRepositoryInterface $carRepository)
    {
    }

    public function __invoke(AddCarCommand $command): void
    {
        $car = new Car(
            id: CarId::generate(),
            brand: $command->brand,
            model: $command->model,
            pricePerDay: $command->pricePerDay,
            available: true
        );

        $this->carRepository->save(car: $car);
    }
}
