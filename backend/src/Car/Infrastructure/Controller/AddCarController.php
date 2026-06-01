<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\AddCar\AddCarCommand;
use App\Car\Infrastructure\Request\AddCarRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @see AddCarCommand
 */
class AddCarController extends AbstractController
{
    #[Route(path: '/api/cars', name: 'api_add_car', methods: ['POST'])]
    #[IsGranted(attribute: 'ROLE_ADMIN')]
    public function __invoke(
        #[MapRequestPayload] AddCarRequest $request,
        MessageBusInterface $messageBus
    ): JsonResponse {
        try {
            $messageBus->dispatch(message: new AddCarCommand(
                brand: $request->brand,
                model: $request->model,
                pricePerDay: $request->pricePerDay
            ));
        } catch (HandlerFailedException $e) {
            $originalException = $e->getPrevious() ?? $e;
            return new JsonResponse(
                data: ['error' => $originalException->getMessage()],
                status: JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse(
            data: ['message' => 'Car added successfully.'],
            status: JsonResponse::HTTP_CREATED
        );
    }
}
