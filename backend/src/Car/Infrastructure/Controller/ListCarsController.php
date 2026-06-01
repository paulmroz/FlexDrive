<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Query\ListCars\ListCarsQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see ListCarsQuery
 */
class ListCarsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    #[Route(path: '/api/cars', name: 'api_list_cars', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        /** @var array<mixed> $cars */
        $cars = $this->handle(message: new ListCarsQuery());

        return new JsonResponse(data: $cars, status: JsonResponse::HTTP_OK);
    }
}
