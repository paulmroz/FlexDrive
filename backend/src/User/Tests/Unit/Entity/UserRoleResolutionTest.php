<?php

declare(strict_types=1);

namespace App\User\Tests\Unit\Entity;

use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\TestCase;

class UserRoleResolutionTest extends TestCase
{
    public function testDefaultUserAlwaysPossessesRoleUser(): void
    {
        $user = new User(
            id: UserId::generate(),
            email: new Email(value: 'user@example.com'),
            password: 'password',
            roles: []
        );

        $this->assertContains('ROLE_USER', $user->getRoles());
    }

    public function testRoleListFiltersDuplicatesAndPreservesCustomRoles(): void
    {
        $user = new User(
            id: UserId::generate(),
            email: new Email(value: 'user@example.com'),
            password: 'password',
            roles: ['ROLE_ADMIN', 'ROLE_USER']
        );

        $this->assertEqualsCanonicalizing(
            ['ROLE_ADMIN', 'ROLE_USER'],
            $user->getRoles()
        );
    }
}
