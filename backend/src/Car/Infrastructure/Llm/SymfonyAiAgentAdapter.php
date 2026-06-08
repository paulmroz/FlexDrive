<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Llm;

use App\Car\Application\Service\AiAgentPortInterface;
use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\DTO\LlmAdvisorResponse;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Redis;

class SymfonyAiAgentAdapter implements AiAgentPortInterface
{
    public function __construct(
        private AgentInterface $agent,
        private Redis $redis
    ) {
    }

    public function getChatSuggestions(string $sessionId, VibePrompt $prompt, array $availableCars): LlmAdvisorResponse
    {
        $context = json_encode(value: $availableCars);
        $systemPrompt = "You are a friendly and helpful AI Fleet Advisor for DriveAgency. " .
            "Your goal is to recommend the best rental cars from our available fleet based on the user's needs, budget, and plans. " .
            "Available cars in our fleet: " . $context . "\n\n" .
            "Instructions:\n" .
            "1. If the user's request is vague, brief, or lacks key details (e.g. they just say 'hello', or 'I want a car', or their budget/dates/preferences are unclear), DO NOT suggest any cars. Keep the 'suggestions' array empty. Instead, write a friendly response in the 'message' field asking clarifying questions (e.g., about their budget, group size, passenger needs, style, or trip plans).\n" .
            "2. If you have enough details to recommend cars, populate the 'suggestions' array with matching car IDs and a brief custom 'reason' for each. Also, write a friendly summary of your suggestions in the 'message' field.\n" .
            "3. ONLY recommend cars that are present in the provided list. Do not make up cars.\n" .
            "4. Your response must strictly match the output schema.";

        $historyKey = 'chat_history.' . $sessionId;
        $historyData = $this->redis->get(key: $historyKey);
        $history = $historyData ? json_decode(json: $historyData, associative: true) : [];

        $bagMessages = [
            Message::forSystem(content: $systemPrompt)
        ];

        foreach ($history as $msg) {
            if ($msg['role'] === 'user') {
                $bagMessages[] = Message::ofUser($msg['content']);
            } elseif ($msg['role'] === 'assistant') {
                $bagMessages[] = Message::ofAssistant($msg['content']);
            }
        }

        $bagMessages[] = Message::ofUser($prompt->getValue());

        $messages = new MessageBag(...$bagMessages);

        $result = $this->agent->call(
            messages: $messages,
            options: [
                'response_format' => LlmAdvisorResponse::class,
            ]
        );

        $responseObj = null;
        if (method_exists(object_or_class: $result, method: 'getContent')) {
            $responseObj = $result->getContent();
        } else {
            $responseObj = $result->asObject();
        }

        if ($responseObj instanceof LlmAdvisorResponse) {
            $history[] = ['role' => 'user', 'content' => $prompt->getValue()];
            $history[] = ['role' => 'assistant', 'content' => $responseObj->message];

            if (count($history) > 20) {
                $history = array_slice(array: $history, offset: -20);
            }

            $this->redis->setex(
                key: $historyKey,
                expire: 3600,
                value: json_encode(value: $history)
            );
        }

        return $responseObj;
    }
}
