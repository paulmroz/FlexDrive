<?php

declare(strict_types=1);

namespace App\Car\Application\Service;

use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\DTO\LlmAdvisorResponse;

interface AiAgentPortInterface
{
    public function getChatSuggestions(string $sessionId, VibePrompt $prompt, array $availableCars): LlmAdvisorResponse;
}
