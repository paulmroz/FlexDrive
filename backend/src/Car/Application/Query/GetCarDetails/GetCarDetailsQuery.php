<?php

declare(strict_types=1);

namespace App\Car\Application\Query\GetCarDetails;

class GetCarDetailsQuery
{
    public function __construct(
        public readonly string $id
    ) {
    }
}
