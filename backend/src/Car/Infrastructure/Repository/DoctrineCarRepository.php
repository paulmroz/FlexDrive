<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Repository;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Car>
 */
class DoctrineCarRepository extends ServiceEntityRepository implements CarRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(registry: $registry, entityClass: Car::class);
    }

    public function save(Car $car): void
    {
        $this->getEntityManager()->persist($car);
        $this->getEntityManager()->flush();
    }

    public function remove(Car $car): void
    {
        $this->getEntityManager()->remove($car);
        $this->getEntityManager()->flush();
    }

    public function findById(CarId $id, ?int $lockMode = null): ?Car
    {
        return $this->find(id: $id->getValue(), lockMode: $lockMode);
    }

    /**
     * @return array<Car>
     */
    public function findAllCars(): array
    {
        return $this->findAll();
    }
}
