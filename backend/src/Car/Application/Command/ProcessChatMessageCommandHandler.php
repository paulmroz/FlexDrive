<?php

declare(strict_types=1);

namespace App\Car\Application\Command;

use App\Car\Application\Command\ProcessChatMessageCommand;
use App\Car\Application\Service\AiAgentPortInterface;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Car\Domain\ValueObject\VibePrompt;
use App\Car\Application\Service\ChatPublisherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ProcessChatMessageCommandHandler
{
    public function __construct(
        private AiAgentPortInterface $aiAgent,
        private CarRepositoryInterface $carRepository,
        private ChatPublisherInterface $chatPublisher
    ) {
    }

    public function __invoke(ProcessChatMessageCommand $command): void
    {
        $sessionId = $command->getSessionId();
        $prompt = new VibePrompt(value: $command->getMessageContent());

        $cars = $this->carRepository->findAvailableCars();
        $availableList = [];

        foreach ($cars as $car) {
            $availableList[] = [
                'id' => $car->getId()->getValue(),
                'brand' => $car->getBrand(),
                'model' => $car->getModel(),
            ];
        }

        $response = $this->aiAgent->getChatSuggestions(
            sessionId: $sessionId,
            prompt: $prompt,
            availableCars: $availableList
        );

        $validSuggestions = [];
        $existingMap = [];

        foreach ($availableList as $carData) {
            $existingMap[$carData['id']] = true;
        }

        foreach ($response->suggestions as $suggestion) {
            if (array_key_exists(key: $suggestion->carId, array: $existingMap)) {
                $validSuggestions[] = [
                    'carId' => $suggestion->carId,
                    'reason' => $suggestion->reason,
                ];
            }
        }

        $this->chatPublisher->publish(
            sessionId: $sessionId,
            suggestions: $validSuggestions,
            message: $response->message
        );
    }
}
