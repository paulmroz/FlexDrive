<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Repository;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\CarId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
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

    public function findById(CarId $id): ?Car
    {
        return $this->find(id: $id->getValue());
    }

    public function findByIdWithWriteLock(CarId $id): ?Car
    {
        return $this->find(id: $id->getValue(), lockMode: LockMode::PESSIMISTIC_WRITE);
    }

    /**
     * @param CarId[] $ids
     * @return Car[]
     */
    public function findByIds(array $ids): array
    {
        $rawIds = array_map(callback: fn(CarId $id): string => $id->getValue(), array: $ids);

        return $this->findBy(criteria: ['id' => $rawIds]);
    }

    /**
     * @return array<Car>
     */
    public function findAllCars(): array
    {
        return $this->findAll();
    }

    /**
     * @return array<Car>
     */
    public function findAvailableCars(): array
    {
        return $this->findBy(criteria: ['available' => true]);
    }

    /**
     * @return array<Car>
     */
    public function findAvailableCarsByCriteria(?string $brand = null, ?string $model = null, ?int $maxPricePerDay = null, ?int $minPricePerDay = null): array
    {
        $qb = $this->createQueryBuilder(alias: 'c')
            ->andWhere('c.available = :available')
            ->setParameter('available', true);

        if (null !== $brand) {
            $qb->andWhere('LOWER(c.brand) LIKE :brand')
               ->setParameter('brand', '%' . strtolower(string: $brand) . '%');
        }

        if (null !== $model) {
            $qb->andWhere('LOWER(c.model) LIKE :model')
               ->setParameter('model', '%' . strtolower(string: $model) . '%');
        }

        if (null !== $maxPricePerDay) {
            $qb->andWhere('c.pricePerDay <= :maxPrice')
               ->setParameter('maxPrice', $maxPricePerDay);
        }

        if (null !== $minPricePerDay) {
            $qb->andWhere('c.pricePerDay >= :minPrice')
               ->setParameter('minPrice', $minPricePerDay);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array<Car>
     */
    public function findFallbackCars(?int $maxPricePerDay = null, int $limit = 5): array
    {
        $qb = $this->createQueryBuilder(alias: 'c')
            ->andWhere('c.available = :available')
            ->setParameter('available', true);

        if (null !== $maxPricePerDay) {
            // Give preference to cars around this budget, but ignore brand/model.
            // A simple approach is just enforcing the price or finding cheapest.
            $qb->andWhere('c.pricePerDay <= :maxPrice')
               ->setParameter('maxPrice', $maxPricePerDay + 20); // slightly loose fallback
        }

        $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }
}

