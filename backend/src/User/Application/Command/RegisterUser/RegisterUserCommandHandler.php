<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Security\SecurityUser;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @see RegisterUserCommand
 */
#[AsMessageHandler]
class RegisterUserCommandHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function __invoke(RegisterUserCommand $command): void
    {
        $email = new Email(value: $command->email);

        if (null !== $this->userRepository->findByEmail(email: $email)) {
            throw new InvalidArgumentException(message: 'A user with this email already exists.');
        }

        $userId = UserId::generate();

        $tempUser = new User(
            id: $userId,
            email: $email,
            password: '',
            roles: ['ROLE_USER']
        );

        $securityUser = new SecurityUser(user: $tempUser);

        $hashedPassword = $this->passwordHasher->hashPassword(
            user: $securityUser,
            plainPassword: $command->password
        );

        $user = new User(
            id: $userId,
            email: $email,
            password: $hashedPassword,
            roles: ['ROLE_USER']
        );

        $this->userRepository->save(user: $user);
    }
}
