<?php

declare(strict_types=1);

namespace App\Car\Tests\Integration;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use App\Car\Domain\Repository\CarRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Doctrine\ORM\EntityManagerInterface;

class CarCrudIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $container = static::getContainer();
        $this->entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(sql: 'TRUNCATE TABLE "users" CASCADE');
        $connection->executeStatement(sql: 'TRUNCATE TABLE "cars" CASCADE');
    }

    public function testAnonymousAccessIsUnauthorized(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/cars',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'brand' => 'Tesla',
                'model' => 'Model 3',
                'pricePerDay' => 2500
            ])
        );

        $this->assertSame(
            Response::HTTP_UNAUTHORIZED,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testStandardUserAccessIsForbidden(): void
    {
        $email = 'user@example.com';
        $password = 'SecureP@ssword123!';

        $this->client->request(
            method: 'POST',
            uri: '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);
        $token = $data['token'];

        $this->client->request(
            method: 'POST',
            uri: '/api/cars',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            content: (string) json_encode(value: [
                'brand' => 'Tesla',
                'model' => 'Model 3',
                'pricePerDay' => 2500
            ])
        );

        $this->assertSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testStandardUserCannotUpdateOrRemoveCar(): void
    {
        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Ford',
            model: 'Focus',
            pricePerDay: 1500,
            available: true
        );
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        $email = 'user2@example.com';
        $password = 'SecureP@ssword123!';

        $this->client->request(
            method: 'POST',
            uri: '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);
        $token = $data['token'];

        $this->client->request(
            method: 'PUT',
            uri: '/api/cars/' . $carId->getValue(),
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            content: (string) json_encode(value: [
                'brand' => 'Ford Updated',
                'model' => 'Focus ST',
                'pricePerDay' => 2000,
                'available' => false
            ])
        );

        $this->assertSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode()
        );

        $this->client->request(
            method: 'DELETE',
            uri: '/api/cars/' . $carId->getValue(),
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        $this->assertSame(
            Response::HTTP_FORBIDDEN,
            $this->client->getResponse()->getStatusCode()
        );
    }

    public function testAdminUserCanAddUpdateAndRemoveCarSuccessfully(): void
    {
        $container = static::getContainer();
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = $container->get(id: 'security.user_password_hasher');

        $email = 'admin@example.com';
        $password = 'adminpassword123';
        $userId = UserId::generate();

        $tempUser = new User(
            id: $userId,
            email: new Email(value: $email),
            password: '',
            roles: ['ROLE_ADMIN']
        );

        $securityUser = new SecurityUser(user: $tempUser);
        $hashedPassword = $passwordHasher->hashPassword(
            user: $securityUser,
            plainPassword: $password
        );

        $adminUser = new User(
            id: $userId,
            email: new Email(value: $email),
            password: $hashedPassword,
            roles: ['ROLE_ADMIN']
        );

        $this->entityManager->persist($adminUser);
        $this->entityManager->flush();

        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password
            ])
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);
        $token = $data['token'];

        $this->client->request(
            method: 'POST',
            uri: '/api/cars',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            content: (string) json_encode(value: [
                'brand' => 'Tesla',
                'model' => 'Model X',
                'pricePerDay' => 4500
            ])
        );

        $this->assertSame(
            Response::HTTP_CREATED,
            $this->client->getResponse()->getStatusCode()
        );

        $carsRepository = $container->get(id: CarRepositoryInterface::class);
        $cars = $carsRepository->findAllCars();
        $this->assertCount(1, $cars);
        $car = $cars[0];
        $carIdStr = $car->getId()->getValue();

        $this->client->request(
            method: 'PUT',
            uri: '/api/cars/' . $carIdStr,
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ],
            content: (string) json_encode(value: [
                'brand' => 'Tesla Updated',
                'model' => 'Model S Plaid',
                'pricePerDay' => 9900,
                'available' => false
            ])
        );

        $this->assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedCar = $carsRepository->findById(new CarId($carIdStr));
        $this->assertNotNull($updatedCar);
        $this->assertSame('Tesla Updated', $updatedCar->getBrand());
        $this->assertSame('Model S Plaid', $updatedCar->getModel());
        $this->assertSame(9900, $updatedCar->getPricePerDay());
        $this->assertFalse($updatedCar->isAvailable());

        $this->client->request(
            method: 'DELETE',
            uri: '/api/cars/' . $carIdStr,
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token
            ]
        );

        $this->assertSame(
            Response::HTTP_OK,
            $this->client->getResponse()->getStatusCode()
        );

        $connection = $this->entityManager->getConnection();
        $count = $connection->fetchOne('SELECT COUNT(*) FROM "cars"');
        $this->assertEquals(0, $count);
    }
}
