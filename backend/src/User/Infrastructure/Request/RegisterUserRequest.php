<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email should not be blank.')]
        #[Assert\Email(message: 'Invalid email format.')]
        public readonly string $email,
        #[Assert\NotBlank(message: 'Password should not be blank.')]
        #[Assert\Length(
            min: 8,
            minMessage: 'Password must be at least 8 characters long.'
        )]
        public readonly string $password
    ) {
    }
}
