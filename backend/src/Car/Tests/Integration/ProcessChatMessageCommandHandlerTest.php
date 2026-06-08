<?php

declare(strict_types=1);

namespace App\Car\Tests\Integration;

use App\Car\Application\Command\ProcessChatMessageCommand;
use App\Car\Application\Command\ProcessChatMessageCommandHandler;
use App\Car\Application\Service\AiAgentPortInterface;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Application\DTO\LlmAdvisorResponse;
use App\Car\Application\DTO\LlmCarSuggestion;
use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use PHPUnit\Framework\TestCase;
use Redis;

class ProcessChatMessageCommandHandlerTest extends TestCase
{
    public function testHandlerExecutesAndPublishesToRedis(): void
    {
        $carId = CarId::generate()->getValue();
        $car = new Car(
            id: new CarId(value: $carId),
            brand: 'Tesla',
            model: 'Model 3',
            pricePerDay: 150,
            available: true
        );

        $repository = $this->createMock(CarRepositoryInterface::class);
        $repository->method('findAvailableCars')->willReturn([$car]);

        $aiResponse = new LlmAdvisorResponse(suggestions: [
            new LlmCarSuggestion(carId: $carId, reason: 'matches electric preference'),
        ]);

        $port = $this->createMock(AiAgentPortInterface::class);
        $port->method('getChatSuggestions')->willReturn($aiResponse);

        $redis = $this->createMock(Redis::class);
        $redis->expects($this->once())
            ->method('setex')
            ->with(...[
                'chat_result.session123',
                300,
                $this->callback(callback: function (string $payload) use ($carId): bool {
                    $data = json_decode(json: $payload, associative: true);
                    return 'completed' === $data['status'] && $carId === $data['suggestions'][0]['carId'];
                })
            ]);

        $redis->expects($this->once())
            ->method('publish')
            ->with(...[
                'chat.session123',
                $this->callback(callback: function (string $payload) use ($carId): bool {
                    $data = json_decode(json: $payload, associative: true);
                    return 'completed' === $data['status'] && $carId === $data['suggestions'][0]['carId'];
                })
            ]);

        $handler = new ProcessChatMessageCommandHandler(
            aiAgent: $port,
            carRepository: $repository,
            redis: $redis
        );

        $handler->__invoke(command: new ProcessChatMessageCommand(
            sessionId: 'session123',
            messageContent: 'I want an electric car'
        ));
    }
}
