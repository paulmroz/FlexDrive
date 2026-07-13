<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Query\GetCarDetails\GetCarDetailsQuery;
use App\Car\Application\Query\ListCars\CarReadModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class GetCarDetailsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    #[Route(path: '/api/cars/{id}', name: 'api_get_car', methods: ['GET'])]
    public function __invoke(string $id): JsonResponse
    {
        /** @var CarReadModel $car */
        $car = $this->handle(message: new GetCarDetailsQuery(id: $id));

        return new JsonResponse(data: $car, status: JsonResponse::HTTP_OK);
    }
}
