<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\UpdateCar\UpdateCarCommand;
use App\Car\Infrastructure\Request\UpdateCarRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @see UpdateCarCommand
 */
class UpdateCarController extends AbstractController
{
    #[Route(path: '/api/cars/{id}', name: 'api_update_car', methods: ['PUT'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    public function __invoke(
        string $id,
        #[MapRequestPayload] UpdateCarRequest $request,
        MessageBusInterface $messageBus
    ): JsonResponse {
        try {
            $messageBus->dispatch(message: new UpdateCarCommand(
                id: $id,
                brand: $request->brand,
                model: $request->model,
                pricePerDay: $request->pricePerDay,
                available: $request->available
            ));
        } catch (HandlerFailedException $e) {
            $originalException = $e->getPrevious() ?? $e;
            return new JsonResponse(
                data: ['error' => $originalException->getMessage()],
                status: JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse(
            data: ['message' => 'Car updated successfully.'],
            status: JsonResponse::HTTP_OK
        );
    }
}
