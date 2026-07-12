<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Security;

use App\User\Domain\Entity\User;
use App\Shared\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Email;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUserInterface;

class SecurityUser implements UserInterface, JWTUserInterface
{
    public function __construct(private readonly User $user)
    {
    }

    public static function createFromPayload($username, array $payload): self
    {
        $userId = $payload['id'] ?? $payload['sub'] ?? '';
        $user = new User(
            id: new UserId(value: $userId),
            email: new Email(value: $username),
            password: ''
        );

        return new self(user: $user);
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->user->getEmail()->getValue();
    }
}
