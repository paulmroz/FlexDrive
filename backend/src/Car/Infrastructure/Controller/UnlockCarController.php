<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\UnlockCar\UnlockCarCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class UnlockCarController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    #[Route(path: '/api/cars/{id}/unlock', name: 'api_unlock_car', methods: ['POST'])]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $data = json_decode(content: $request->getContent(), associative: true) ?? [];
        $sagaId = $data['saga_id'] ?? null;

        $this->handle(message: new UnlockCarCommand(id: $id, sagaId: $sagaId));

        return new JsonResponse(data: ['status' => 'unlocked'], status: JsonResponse::HTTP_OK);
    }
}
