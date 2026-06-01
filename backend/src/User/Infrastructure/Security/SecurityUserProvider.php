<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
class SecurityUserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findByEmail(email: new Email(value: $identifier));

        if (null === $user) {
            throw new UserNotFoundException(message: "User with email \"{$identifier}\" not found.");
        }

        return new SecurityUser(user: $user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            $class = $user::class;
            throw new UnsupportedUserException(message: "Instances of \"{$class}\" are not supported.");
        }

        return $this->loadUserByIdentifier(identifier: $user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }
}
