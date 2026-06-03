<?php

declare(strict_types=1);

namespace App\Car\Domain\ValueObject;

use InvalidArgumentException;

class VibePrompt
{
    public function __construct(private readonly string $value)
    {
        $trimmed = trim(string: $value);

        if ('' === $trimmed) {
            throw new InvalidArgumentException(message: 'Prompt cannot be empty.');
        }

        if (mb_strlen($trimmed) > 500) {
            throw new InvalidArgumentException(message: 'Prompt cannot exceed 500 characters.');
        }
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
