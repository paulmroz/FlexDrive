<?php

declare(strict_types=1);

namespace App\User\Application\Command\RegisterUser;

use App\User\Application\Command\RegisterUser\RegisterUserCommandHandler;

/**
 * @see RegisterUserCommandHandler
 */
class RegisterUserCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {
    }
}
