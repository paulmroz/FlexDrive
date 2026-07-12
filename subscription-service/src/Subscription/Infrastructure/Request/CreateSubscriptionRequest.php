<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriptionRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Car ID should not be blank.')]
        #[Assert\Uuid(message: 'Car ID must be a valid UUID.')]
        public readonly string $carId,

        #[Assert\NotBlank(message: 'Start date should not be blank.')]
        #[Assert\DateTime(format: 'Y-m-d H:i:s', message: 'Start date must be in Y-m-d H:i:s format.')]
        public readonly string $startDate,

        #[Assert\DateTime(format: 'Y-m-d H:i:s', message: 'End date must be in Y-m-d H:i:s format.')]
        public readonly ?string $endDate = null
    ) {
    }
}
