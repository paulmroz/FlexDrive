<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Command\LockCar\LockCarCommand;
use App\Car\Api\Dto\CarDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class LockCarController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    #[Route(path: '/api/cars/{id}/lock', name: 'api_lock_car', methods: ['POST'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var CarDto $carDto */
        $carDto = $this->handle(message: new LockCarCommand(id: $id));

        return new JsonResponse(data: $carDto, status: JsonResponse::HTTP_OK);
    }
}
