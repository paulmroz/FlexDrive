<?php

declare(strict_types=1);

namespace App\Shared\Tests\Unit\ValueObject;

use App\Shared\Domain\ValueObject\UserId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UserIdTest extends TestCase
{
    public function testCanBeCreatedFromValidUuid(): void
    {
        $validUuid = '550e8400-e29b-41d4-a716-446655440000';
        $userId = new UserId(value: $validUuid);

        $this->assertSame($validUuid, $userId->getValue());
        $this->assertSame($validUuid, (string) $userId);
    }

    public function testCanGenerateRandomUuid(): void
    {
        $userId = UserId::generate();

        $this->assertNotEmpty($userId->getValue());
    }

    public function testThrowsExceptionOnInvalidUuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID format.');

        new UserId(value: 'invalid-uuid');
    }
}
