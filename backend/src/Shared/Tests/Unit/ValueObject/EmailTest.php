<?php

declare(strict_types=1);

namespace App\Shared\Tests\Unit\ValueObject;

use App\Shared\Domain\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function testCanBeCreatedFromValidEmail(): void
    {
        $validEmail = 'user@example.com';
        $email = new Email(value: $validEmail);

        $this->assertSame($validEmail, $email->getValue());
    }

    public function testNormalizesEmailToLowercase(): void
    {
        $email = new Email(value: 'UsEr@ExAmPlE.cOm');

        $this->assertSame('user@example.com', $email->getValue());
    }

    public function testThrowsExceptionOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email address: invalid-email');

        new Email(value: 'invalid-email');
    }
}
