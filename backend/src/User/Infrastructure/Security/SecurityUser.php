<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private readonly User $user)
    {
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getPassword(): ?string
    {
        return $this->user->getPassword();
    }

    public function getRoles(): array
    {
        return $this->user->getRoles();
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail()->getValue();
    }
}
