<?php

declare(strict_types=1);

namespace App\Car\Application\Service;

use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\DTO\LlmAdvisorResponse;
use App\Car\Application\DTO\LlmCarSearchCriteria;

interface AiAgentPortInterface
{
    public function extractCriteria(VibePrompt $prompt): LlmCarSearchCriteria;

    public function getChatSuggestions(string $sessionId, VibePrompt $prompt, array $candidateCars): LlmAdvisorResponse;
}
