<?php

declare(strict_types=1);

namespace App\Car\Tests\Functional;

use App\Car\Application\Command\AddCar\AddCarCommand;
use App\Car\Application\Command\AddCar\AddCarCommandHandler;
use App\Car\Application\Command\RemoveCar\RemoveCarCommand;
use App\Car\Application\Command\RemoveCar\RemoveCarCommandHandler;
use App\Car\Application\Command\UpdateCar\UpdateCarCommand;
use App\Car\Application\Command\UpdateCar\UpdateCarCommandHandler;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use InvalidArgumentException;

class CarWorkflowFunctionalTest extends KernelTestCase
{
    private CarRepositoryInterface $carRepository;
    private AddCarCommandHandler $addHandler;
    private UpdateCarCommandHandler $updateHandler;
    private RemoveCarCommandHandler $removeHandler;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $container = self::getContainer();
        $this->carRepository = $container->get(id: CarRepositoryInterface::class);
        $this->addHandler = $container->get(id: AddCarCommandHandler::class);
        $this->updateHandler = $container->get(id: UpdateCarCommandHandler::class);
        $this->removeHandler = $container->get(id: RemoveCarCommandHandler::class);

        $entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $entityManager->getConnection()->executeStatement(sql: 'TRUNCATE TABLE "cars" CASCADE');
    }

    public function testCarCanBeAddedUpdatedAndRemovedViaHandlers(): void
    {
        $addCommand = new AddCarCommand(
            brand: 'Tesla',
            model: 'Model S',
            pricePerDay: 5000
        );
        $this->addHandler->__invoke(command: $addCommand);

        $cars = $this->carRepository->findAllCars();
        $this->assertCount(1, $cars);
        $car = $cars[0];
        $carId = $car->getId();

        $this->assertSame('Tesla', $car->getBrand());
        $this->assertSame('Model S', $car->getModel());
        $this->assertSame(5000, $car->getPricePerDay());
        $this->assertTrue($car->isAvailable());

        $updateCommand = new UpdateCarCommand(
            id: $carId->getValue(),
            brand: 'Tesla Updated',
            model: 'Model S Plaid',
            pricePerDay: 9000,
            available: false
        );
        $this->updateHandler->__invoke(command: $updateCommand);

        $updatedCar = $this->carRepository->findById(id: $carId);
        $this->assertNotNull($updatedCar);
        $this->assertSame('Tesla Updated', $updatedCar->getBrand());
        $this->assertSame(9000, $updatedCar->getPricePerDay());
        $this->assertFalse($updatedCar->isAvailable());

        $removeCommand = new RemoveCarCommand(id: $carId->getValue());
        $this->removeHandler->__invoke(command: $removeCommand);

        $deletedCar = $this->carRepository->findById(id: $carId);
        $this->assertNull($deletedCar);
    }

    public function testUpdateThrowsExceptionIfCarNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Car not found.');

        $updateCommand = new UpdateCarCommand(
            id: CarId::generate()->getValue(),
            brand: 'Unknown',
            model: 'Unknown',
            pricePerDay: 1000,
            available: true
        );

        $this->updateHandler->__invoke(command: $updateCommand);
    }
}
