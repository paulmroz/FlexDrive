<?php

declare(strict_types=1);

namespace App\Car\Application\Command\UnlockCar;

use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;

#[AsMessageHandler]
class UnlockCarCommandHandler
{
    public function __construct(
        private readonly CarRepositoryInterface $carRepository
    ) {
    }

    public function __invoke(UnlockCarCommand $command): void
    {
        $car = $this->carRepository->findByIdWithWriteLock(id: new CarId(value: $command->id));

        if (null === $car) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        // Failsafe: if the car is locked by another Saga, do not unlock it
        if (null !== $command->sagaId && null !== $car->getLockedBySaga() && $car->getLockedBySaga() !== $command->sagaId) {
            return; 
        }

        $car->release();
        $this->carRepository->save(car: $car);
    }
}
