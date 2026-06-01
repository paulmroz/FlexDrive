<?php

declare(strict_types=1);

namespace App\Car\Tests\Unit\Entity;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use PHPUnit\Framework\TestCase;

class CarTest extends TestCase
{
    public function testCarCanBeCreatedAndUpdated(): void
    {
        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Tesla',
            model: 'Model 3',
            pricePerDay: 2500,
            available: true
        );

        $this->assertEquals($carId, $car->getId());
        $this->assertSame('Tesla', $car->getBrand());
        $this->assertSame('Model 3', $car->getModel());
        $this->assertSame(2500, $car->getPricePerDay());
        $this->assertTrue($car->isAvailable());

        $car->update(
            brand: 'Tesla Updated',
            model: 'Model Y',
            pricePerDay: 3000,
            available: false
        );

        $this->assertSame('Tesla Updated', $car->getBrand());
        $this->assertSame('Model Y', $car->getModel());
        $this->assertSame(3000, $car->getPricePerDay());
        $this->assertFalse($car->isAvailable());
    }
}
