<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Integration;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\Gateway\StripeCheckoutSessionDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class CreateSubscriptionIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private EntityManagerInterface $subscriptionEntityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();
        $this->entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $this->subscriptionEntityManager = $container->get(id: 'doctrine.orm.subscription_entity_manager');
        $connection = $this->entityManager->getConnection();
        $subConnection = $this->subscriptionEntityManager->getConnection();

        $connection->executeStatement(sql: 'TRUNCATE TABLE "users" CASCADE');
        $connection->executeStatement(sql: 'TRUNCATE TABLE "cars" CASCADE');
        $subConnection->executeStatement(sql: 'TRUNCATE TABLE "subscriptions" CASCADE');

        $paymentGatewayClientMock = $this->createMock(originalClassName: PaymentGatewayClientInterface::class);
        $paymentGatewayClientMock->method('createCheckoutSession')->willReturnCallback(callback: function () {
            $id = uniqid();
            return new StripeCheckoutSessionDto(
                sessionId: 'cs_test_mock_' . $id,
                url: 'https://checkout.stripe.com/pay/cs_test_mock_' . $id
            );
        });
        $container->set(id: PaymentGatewayClientInterface::class, service: $paymentGatewayClientMock);
    }

    public function testAnonymousAccessIsUnauthorized(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'carId' => CarId::generate()->getValue(),
                'startDate' => '2026-06-01 10:00:00',
            ])
        );

        $this->assertSame(
            expected: Response::HTTP_UNAUTHORIZED,
            actual: $this->client->getResponse()->getStatusCode()
        );
    }

    public function testCreateSubscriptionSuccessfully(): void
    {
        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000,
            available: true
        );
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        $token = $this->getAuthToken(
            email: 'user@example.com',
            password: 'SecureP@ssword123!'
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-01 12:00:00',
                'endDate' => '2026-06-08 12:00:00',
            ])
        );

        if (Response::HTTP_CREATED !== $this->client->getResponse()->getStatusCode()) {
            fwrite(STDERR, $this->client->getResponse()->getContent() . "\n");
        }
        $this->assertSame(
            expected: Response::HTTP_CREATED,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->subscriptionEntityManager->clear();
        $subscription = $this->subscriptionEntityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['carId' => $carId->getValue()]);
        $this->assertNotNull(actual: $subscription);

        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-08 14:59:59',
                'endDate' => '2026-06-15 12:00:00',
            ])
        );

        $this->assertSame(
            expected: Response::HTTP_UNPROCESSABLE_ENTITY,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-08 15:00:00',
                'endDate' => '2026-06-15 12:00:00',
            ])
        );

        if (Response::HTTP_CREATED !== $this->client->getResponse()->getStatusCode()) {
            fwrite(STDERR, $this->client->getResponse()->getContent() . "\n");
        }
        $this->assertSame(
            expected: Response::HTTP_CREATED,
            actual: $this->client->getResponse()->getStatusCode()
        );
    }

    public function testValidationErrors(): void
    {
        $token = $this->getAuthToken(
            email: 'user@example.com',
            password: 'SecureP@ssword123!'
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => 'invalid-uuid',
                'startDate' => 'invalid-date',
            ])
        );

        $this->assertSame(
            expected: Response::HTTP_UNPROCESSABLE_ENTITY,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);

        $this->assertSame(expected: 'https://tools.ietf.org/html/rfc7807', actual: $data['type']);
        $this->assertSame(expected: 'Validation Failed', actual: $data['title']);
        $this->assertSame(expected: 422, actual: $data['status']);
        $this->assertSame(expected: 'One or more fields failed validation.', actual: $data['detail']);
        $this->assertCount(expectedCount: 2, haystack: $data['invalid_params']);

        $names = array_column(array: $data['invalid_params'], column_key: 'name');
        $reasons = array_column(array: $data['invalid_params'], column_key: 'reason');

        $this->assertContains(needle: 'carId', haystack: $names);
        $this->assertContains(needle: 'startDate', haystack: $names);
        $this->assertContains(needle: 'Car ID must be a valid UUID.', haystack: $reasons);
        $this->assertContains(needle: 'Start date must be in Y-m-d H:i:s format.', haystack: $reasons);
    }

    public function testCarNotFound(): void
    {
        $token = $this->getAuthToken(
            email: 'user@example.com',
            password: 'SecureP@ssword123!'
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => CarId::generate()->getValue(),
                'startDate' => '2026-06-01 12:00:00',
            ])
        );

        $this->assertSame(
            expected: Response::HTTP_UNPROCESSABLE_ENTITY,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);
        $this->assertSame(
            expected: 'Car not found.',
            actual: $data['detail']
        );
        $this->assertSame(
            expected: 'Business Rule Violation',
            actual: $data['title']
        );
    }

    public function testCarAlreadyBooked(): void
    {
        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000,
            available: false
        );
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        $token = $this->getAuthToken(
            email: 'user@example.com',
            password: 'SecureP@ssword123!'
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-01 12:00:00',
            ])
        );

        $this->assertSame(
            expected: Response::HTTP_UNPROCESSABLE_ENTITY,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);
        $this->assertSame(
            expected: 'Car is already booked/unavailable.',
            actual: $data['detail']
        );
    }

    private function getAuthToken(string $email, string $password): string
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/register',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password,
            ])
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(value: [
                'email' => $email,
                'password' => $password,
            ])
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);

        return $data['token'];
    }

    public function testListUserSubscriptions(): void
    {
        $token = $this->getAuthToken(
            email: 'list-user@example.com',
            password: 'SecureP@ssword123!'
        );

        // Fetch empty list
        $this->client->request(
            method: 'GET',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $data = json_decode(json: $responseContent, associative: true);
        $this->assertCount(expectedCount: 0, haystack: $data);
    }

    public function testCancelSubscription(): void
    {
        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000,
            available: true
        );
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        $token = $this->getAuthToken(
            email: 'cancel-user@example.com',
            password: 'SecureP@ssword123!'
        );

        // Create a subscription
        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-01 12:00:00',
                'endDate' => '2026-06-08 12:00:00',
            ])
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $createdData = json_decode(json: $responseContent, associative: true);
        $subscriptionId = $createdData['subscriptionId'];

        // Cancel it
        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions/' . $subscriptionId . '/cancel',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->subscriptionEntityManager->clear();
        $subscription = $this->subscriptionEntityManager
            ->getRepository(Subscription::class)
            ->find($subscriptionId);

        $this->assertNotNull(actual: $subscription);
        $this->assertSame(
            expected: 'cancelled',
            actual: $subscription->getStatus()->value
        );
    }

    public function testCreateCheckoutSessionSuccessfully(): void
    {
        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000,
            available: true
        );
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        $token = $this->getAuthToken(
            email: 'checkout-user@example.com',
            password: 'SecureP@ssword123!'
        );

        // Create subscription
        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-01 12:00:00',
                'endDate' => '2026-06-08 12:00:00',
            ])
        );

        $responseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($responseContent);
        $createdData = json_decode(json: $responseContent, associative: true);
        $subscriptionId = $createdData['subscriptionId'];

        // Request checkout session
        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions/' . $subscriptionId . '/checkout',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ]
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $checkoutResponseContent = $this->client->getResponse()->getContent();
        $this->assertNotFalse($checkoutResponseContent);
        $checkoutData = json_decode(json: $checkoutResponseContent, associative: true);
        $this->assertArrayHasKey(key: 'paymentUrl', array: $checkoutData);
        $this->assertStringContainsString(needle: 'stripe.com', haystack: $checkoutData['paymentUrl']);
    }
}
