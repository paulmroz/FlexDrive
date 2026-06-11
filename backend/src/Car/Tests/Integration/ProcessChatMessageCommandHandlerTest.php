<?php

declare(strict_types=1);

namespace App\Car\Tests\Integration;

use App\Car\Application\Command\ProcessChatMessageCommand;
use App\Car\Application\Command\ProcessChatMessageCommandHandler;
use App\Car\Application\Service\AiAgentPortInterface;
use App\Car\Application\Service\ChatPublisherInterface;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Application\DTO\LlmAdvisorResponse;
use App\Car\Application\DTO\LlmCarSearchCriteria;
use App\Car\Application\DTO\LlmCarSuggestion;
use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use PHPUnit\Framework\TestCase;

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
        $repository->method('findAvailableCarsByCriteria')->willReturn([$car]);

        $aiResponse = new LlmAdvisorResponse(suggestions: [
            new LlmCarSuggestion(carId: $carId, reason: 'matches electric preference'),
        ]);

        $port = $this->createMock(AiAgentPortInterface::class);
        $port->method('extractCriteria')->willReturn(new LlmCarSearchCriteria());
        $port->method('getChatSuggestions')->willReturn($aiResponse);

        $publisher = $this->createMock(originalClassName: ChatPublisherInterface::class);
        $publisher->expects($this->once())
            ->method('publish')
            ->with(...[
                'session123',
                [['carId' => $carId, 'reason' => 'matches electric preference']],
                'Here are some cars you might like.'
            ]);

        $handler = new ProcessChatMessageCommandHandler(
            aiAgent: $port,
            carRepository: $repository,
            chatPublisher: $publisher
        );

        $handler->__invoke(command: new ProcessChatMessageCommand(
            sessionId: 'session123',
            messageContent: 'I want an electric car'
        ));
    }
}
