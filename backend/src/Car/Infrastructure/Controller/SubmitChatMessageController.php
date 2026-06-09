<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\ProcessChatMessageCommand;
use App\Car\Infrastructure\Request\SubmitMessageRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class SubmitChatMessageController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus
    ) {
    }

    #[Route(path: '/api/cars/advisor/chat/{sessionId}', name: 'api_cars_advisor_chat', methods: ['POST'])]
    public function __invoke(string $sessionId, #[MapRequestPayload] SubmitMessageRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(message: new ProcessChatMessageCommand(
            sessionId: $sessionId,
            messageContent: $request->message
        ));

        return new JsonResponse(data: null, status: Response::HTTP_ACCEPTED);
    }
}
