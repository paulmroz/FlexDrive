<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Llm;

use App\Car\Application\Service\AiAgentPortInterface;
use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\DTO\LlmAdvisorResponse;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

class SymfonyAiAgentAdapter implements AiAgentPortInterface
{
    public function __construct(
        private AgentInterface $agent
    ) {
    }

    public function getChatSuggestions(string $sessionId, VibePrompt $prompt, array $availableCars): LlmAdvisorResponse
    {
        $context = json_encode(value: $availableCars);

        $messages = new MessageBag(...[
            Message::forSystem('Format recommendations strictly to match schema. Select from: ' . $context),
            Message::ofUser($prompt->getValue()),
        ]);

        $result = $this->agent->call(
            messages: $messages,
            options: [
                'response_format' => LlmAdvisorResponse::class,
            ]
        );

        if (method_exists($result, 'getContent')) {
            return $result->getContent();
        }

        return $result->asObject();
    }
}
