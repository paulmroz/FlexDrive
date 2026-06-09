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
        #[Assert\Regex(
            pattern: '/[a-z]/',
            message: 'Password must contain at least one lowercase letter.'
        )]
        #[Assert\Regex(
            pattern: '/[A-Z]/',
            message: 'Password must contain at least one uppercase letter.'
        )]
        #[Assert\Regex(
            pattern: '/[0-9]/',
            message: 'Password must contain at least one number.'
        )]
        #[Assert\Regex(
            pattern: '/[\W_]/',
            message: 'Password must contain at least one special character.'
        )]
        public readonly string $password
    ) {
    }
}
