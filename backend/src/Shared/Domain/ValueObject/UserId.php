<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;

class UserId
{
    public function __construct(private readonly string $value)
    {
        if (false === Uuid::isValid(uuid: $value)) {
            throw new InvalidArgumentException(message: 'Invalid UUID format.');
        }
    }
    public static function generate(): self
    {
        return new self(value: Uuid::v4()->toString());
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
