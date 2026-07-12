<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use App\Shared\Domain\ValueObject\UserId;
use App\Shared\Domain\ValueObject\Email;

class User
{
    public function __construct(
        private readonly UserId $id,
        private readonly Email $email,
        private readonly string $password
    ) {
    }

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
