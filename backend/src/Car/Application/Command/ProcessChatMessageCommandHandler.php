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

        // Pass 1: Extract criteria
        $criteria = $this->aiAgent->extractCriteria($prompt);

        // Try strict search
        $cars = $this->carRepository->findAvailableCarsByCriteria(
            brand: $criteria->brand,
            model: $criteria->model,
            maxPricePerDay: $criteria->maxPricePerDay,
            minPricePerDay: $criteria->minPricePerDay
        );

        if ([] === $cars) {
            // Strict search failed, get fallbacks
            $cars = $this->carRepository->findFallbackCars(maxPricePerDay: $criteria->maxPricePerDay, limit: 5);
        }

        $candidateList = [];
        foreach ($cars as $car) {
            $candidateList[] = [
                'id' => $car->getId()->getValue(),
                'brand' => $car->getBrand(),
                'model' => $car->getModel(),
                'pricePerDay' => $car->getPricePerDay(),
            ];
        }

        // Pass 2: Let AI generate the final response and pick the best cars
        $response = $this->aiAgent->getChatSuggestions(
            sessionId: $sessionId,
            prompt: $prompt,
            candidateCars: $candidateList
        );

        // The AI is expected to return suggestions that we map to the existing response
        $validSuggestions = [];
        if (property_exists($response, 'suggestions') && is_array($response->suggestions)) {
            $existingMap = [];
            foreach ($candidateList as $carData) {
                $existingMap[$carData['id']] = true;
            }

            foreach ($response->suggestions as $suggestion) {
                // Ensure the AI didn't hallucinate a car ID
                if (isset($suggestion->carId) && array_key_exists(key: $suggestion->carId, array: $existingMap)) {
                    $validSuggestions[] = [
                        'carId' => $suggestion->carId,
                        'reason' => $suggestion->reason ?? 'Matches your request.',
                    ];
                }
            }
        }

        $this->chatPublisher->publish(
            sessionId: $sessionId,
            suggestions: $validSuggestions,
            message: $response->message ?? 'Here are some cars you might like.'
        );
    }
}
