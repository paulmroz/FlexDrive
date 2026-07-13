<?php

declare(strict_types=1);

namespace App\Car\Infrastructure\Api;

use App\Car\Api\CarApiInterface;
use App\Car\Api\Dto\CarDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;
use DomainException;
use RuntimeException;

class HttpCarApi implements CarApiInterface
{
    public function __construct(
        private readonly HttpClientInterface $carClient
    ) {
    }

    public function lockAndValidateCar(string $carId): CarDto
    {
        $response = $this->carClient->request(method: 'POST', url: sprintf('/api/cars/%s/lock', $carId));

        if (Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        if (Response::HTTP_UNPROCESSABLE_ENTITY === $response->getStatusCode()) {
            $data = $response->toArray(throw: false);
            throw new DomainException(message: $data['detail'] ?? 'Car is already booked/unavailable.');
        }

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException(message: 'Failed to lock and validate car.');
        }

        $data = $response->toArray();

        return new CarDto(
            id: $data['id'],
            brand: $data['brand'],
            model: $data['model'],
            pricePerDay: $data['pricePerDay']
        );
    }

    public function getCarDetails(string $carId): CarDto
    {
        $response = $this->carClient->request(method: 'GET', url: sprintf('/api/cars/%s', $carId));

        if (Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
            throw new InvalidArgumentException(message: 'Car not found.');
        }

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException(message: 'Failed to fetch car details.');
        }

        $data = $response->toArray();

        return new CarDto(
            id: $data['id'],
            brand: $data['brand'],
            model: $data['model'],
            pricePerDay: $data['pricePerDay']
        );
    }

    public function getCarDetailsBatch(array $carIds): array
    {
        if (empty($carIds)) {
            return [];
        }

        $response = $this->carClient->request(
            method: 'POST',
            url: '/api/cars/batch',
            options: [
                'json' => ['ids' => $carIds]
            ]
        );

        if (Response::HTTP_OK !== $response->getStatusCode()) {
            throw new RuntimeException(message: 'Failed to fetch batch car details.');
        }

        $data = $response->toArray();
        $result = [];

        foreach ($data as $carData) {
            $result[$carData['id']] = new CarDto(
                id: $carData['id'],
                brand: $carData['brand'],
                model: $carData['model'],
                pricePerDay: $carData['pricePerDay']
            );
        }

        return $result;
    }
}
