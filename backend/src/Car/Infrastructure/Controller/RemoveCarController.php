<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\RemoveCar\RemoveCarCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @see RemoveCarCommand
 */
class RemoveCarController extends AbstractController
{
    #[Route(path: '/api/cars/{id}', name: 'api_remove_car', methods: ['DELETE'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    public function __invoke(
        string $id,
        MessageBusInterface $messageBus
    ): JsonResponse {
        $messageBus->dispatch(message: new RemoveCarCommand(id: $id));

        return new JsonResponse(
            data: ['message' => 'Car removed successfully.'],
            status: JsonResponse::HTTP_OK
        );
    }
}

