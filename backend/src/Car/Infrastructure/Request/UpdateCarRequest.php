<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateCarRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Brand should not be blank.')]
        #[Assert\Length(
            max: 100,
            maxMessage: 'Brand cannot exceed 100 characters.'
        )]
        public readonly string $brand,

        #[Assert\NotBlank(message: 'Model should not be blank.')]
        #[Assert\Length(
            max: 100,
            maxMessage: 'Model cannot exceed 100 characters.'
        )]
        public readonly string $model,

        #[Assert\NotBlank(message: 'Price per day should not be blank.')]
        #[Assert\Positive(message: 'Price per day must be a positive integer.')]
        public readonly int $pricePerDay,

        #[Assert\NotNull(message: 'Available status should not be null.')]
        public readonly bool $available
    ) {
    }
}
