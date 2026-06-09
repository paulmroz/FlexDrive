<?php

declare(strict_types=1);

namespace App\Car\Tests\Unit;

use App\Car\Infrastructure\Llm\SymfonyAiAgentAdapter;
use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\DTO\LlmAdvisorResponse;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\RawResultInterface;
use Symfony\AI\Platform\Metadata\Metadata;
use PHPUnit\Framework\TestCase;
use Redis;

class SymfonyAiAgentAdapterTest extends TestCase
{
    public function testGetChatSuggestionsMaintainsHistory(): void
    {
        $agent = $this->createMock(originalClassName: AgentInterface::class);
        $redis = $this->createMock(originalClassName: Redis::class);

        $sessionId = 'test-session-123';
        $historyKey = 'chat_history.' . $sessionId;

        // Mock Redis containing one user/assistant turn
        $redis->expects($this->once())
            ->method('get')
            ->with($historyKey)
            ->willReturn(value: json_encode(value: [
                ['role' => 'user', 'content' => 'Hello'],
                ['role' => 'assistant', 'content' => 'Hi, how can I help?']
            ]));

        $mockLlmResultObj = new LlmAdvisorResponse(
            suggestions: [],
            message: 'I recommend the Tesla.'
        );

        $mockLlmResult = new TestResultDouble(content: $mockLlmResultObj);

        $agent->expects($this->once())
            ->method('call')
            ->with(
                $this->isInstanceOf(className: MessageBag::class),
                $this->callback(callback: function (array $options): bool {
                    return $options['response_format'] === LlmAdvisorResponse::class;
                })
            )
            ->willReturn(value: $mockLlmResult);

        // Verify Redis saves the updated history containing the new turn
        $redis->expects($this->once())
            ->method('setex')
            ->with(...[
                $historyKey,
                3600,
                $this->callback(callback: function (string $payload): bool {
                    $history = json_decode(json: $payload, associative: true);
                    return count($history) === 4 &&
                        $history[2]['role'] === 'user' &&
                        $history[2]['content'] === 'I want a fast car' &&
                        $history[3]['role'] === 'assistant' &&
                        $history[3]['content'] === 'I recommend the Tesla.';
                })
            ]);

        $adapter = new SymfonyAiAgentAdapter(
            agent: $agent,
            redis: $redis,
            advisorSystemPrompt: 'Available cars: %s'
        );
        $response = $adapter->getChatSuggestions(
            sessionId: $sessionId,
            prompt: new VibePrompt(value: 'I want a fast car'),
            availableCars: []
        );

        $this->assertSame(expected: $mockLlmResultObj, actual: $response);
    }
}

class TestResultDouble implements ResultInterface
{
    public function __construct(private readonly object $content)
    {
    }

    public function getContent(): object
    {
        return $this->content;
    }

    public function getRawResult(): ?RawResultInterface
    {
        return null;
    }

    public function setRawResult(RawResultInterface $rawResult): void
    {
    }

    public function getMetadata(): Metadata
    {
        return new Metadata();
    }
}
