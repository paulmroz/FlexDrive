<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct(registry: $registry, entityClass: User::class);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findById(UserId $id): ?User
    {
        return $this->find(id: $id->getValue());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->findOneBy(criteria: ['email' => $email->getValue()]);
    }
}
