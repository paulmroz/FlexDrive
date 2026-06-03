<?php

declare(strict_types=1);

namespace App\Car\Application\DTO;

use App\Car\Application\DTO\LlmCarSuggestion;

class LlmAdvisorResponse
{
    /**
     * @param LlmCarSuggestion[] $suggestions
     */
    public function __construct(
        public array $suggestions
    ) {
    }
}
