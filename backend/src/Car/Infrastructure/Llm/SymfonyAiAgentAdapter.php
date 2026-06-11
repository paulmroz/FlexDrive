<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Llm;

use App\Car\Application\Service\AiAgentPortInterface;
use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\DTO\LlmAdvisorResponse;
use App\Car\Application\DTO\LlmCarSearchCriteria;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Redis;

class SymfonyAiAgentAdapter implements AiAgentPortInterface
{
    public function __construct(
        private AgentInterface $agent,
        private Redis $redis,
        private string $advisorSystemPrompt,
        private string $extractorSystemPrompt
    ) {
    }

    public function extractCriteria(VibePrompt $prompt): LlmCarSearchCriteria
    {
        $messages = new MessageBag(
            Message::forSystem(content: $this->extractorSystemPrompt),
            Message::ofUser($prompt->getValue())
        );

        $result = $this->agent->call(
            messages: $messages,
            options: [
                'response_format' => LlmCarSearchCriteria::class,
            ]
        );

        if (method_exists(object_or_class: $result, method: 'getContent')) {
            $responseObj = $result->getContent();
        } else {
            $responseObj = $result->asObject();
        }

        if ($responseObj instanceof LlmCarSearchCriteria) {
            return $responseObj;
        }

        return new LlmCarSearchCriteria();
    }

    public function getChatSuggestions(string $sessionId, VibePrompt $prompt, array $candidateCars): LlmAdvisorResponse
    {
        $context = json_encode(value: $candidateCars);
        $systemPrompt = sprintf($this->advisorSystemPrompt, $context);

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
