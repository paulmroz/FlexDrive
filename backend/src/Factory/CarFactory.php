<?php

declare(strict_types=1);

namespace App\Factory;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Car>
 */
class CarFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Car::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'id' => CarId::generate(),
            'brand' => self::faker()->company(),
            'model' => self::faker()->word(),
            'pricePerDay' => self::faker()->numberBetween(2000, 10000),
            'available' => true,
        ];
    }
}
