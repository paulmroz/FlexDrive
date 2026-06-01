<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

use InvalidArgumentException;

class Email
{
    /** @var non-empty-string */
    private readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim(string: $value);
        if ('' === $trimmed) {
            throw new InvalidArgumentException(message: 'Email address cannot be empty.');
        }

        if (false === filter_var(value: $trimmed, filter: FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(message: 'Invalid email address: ' . $trimmed);
        }

        $this->value = strtolower(string: $trimmed);
    }

    /**
     * @return non-empty-string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return non-empty-string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
