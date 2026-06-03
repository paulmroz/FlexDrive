<?php

declare(strict_types=1);

namespace App\Car\Application\DTO;

class LlmCarSuggestion
{
    public function __construct(
        public string $carId,
        public string $reason
    ) {
    }
}
