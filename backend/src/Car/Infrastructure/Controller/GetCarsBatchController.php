<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Controller;

use App\Car\Application\Query\GetCarsBatch\GetCarsBatchQuery;
use App\Car\Application\Query\ListCars\CarReadModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use InvalidArgumentException;

class GetCarsBatchController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    #[Route(path: '/api/cars/batch', name: 'api_cars_batch', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode(
            json: (string) $request->getContent(),
            associative: true
        );

        if (!isset($data['ids']) || !is_array($data['ids'])) {
            throw new InvalidArgumentException(message: 'Missing or invalid "ids" parameter in request body.');
        }

        /** @var CarReadModel[] $cars */
        $cars = $this->handle(message: new GetCarsBatchQuery(ids: $data['ids']));

        return new JsonResponse(data: $cars, status: JsonResponse::HTTP_OK);
    }
}
