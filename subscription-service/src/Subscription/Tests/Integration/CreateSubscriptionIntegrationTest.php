<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Integration;

use App\Car\Api\CarApiInterface;
use App\Car\Api\Dto\CarDto;
use App\Car\Domain\ValueObject\CarId;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\Gateway\StripeCheckoutSessionDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\JWTUser;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\Email;
use InvalidArgumentException;
use DomainException;

class CreateSubscriptionIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    /** @var \PHPUnit\Framework\MockObject\MockObject&CarApiInterface */
    private $carApiMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();
        $this->entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(sql: 'TRUNCATE TABLE "subscriptions" CASCADE');

        $paymentGatewayClientMock = $this->createMock(originalClassName: PaymentGatewayClientInterface::class);
        $paymentGatewayClientMock->method('createCheckoutSession')->willReturnCallback(callback: function () {
            $id = uniqid();
            return new StripeCheckoutSessionDto(
                sessionId: 'cs_test_mock_' . $id,
                url: 'https://checkout.stripe.com/pay/cs_test_mock_' . $id
            );
        });
        $container->set(id: PaymentGatewayClientInterface::class, service: $paymentGatewayClientMock);

        $this->carApiMock = $this->createMock(originalClassName: CarApiInterface::class);
        $container->set(id: CarApiInterface::class, service: $this->carApiMock);
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
        
        $this->carApiMock->method('lockAndValidateCar')->willReturn(new CarDto(
            id: $carId->getValue(),
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000
        ));

        $token = $this->getAuthToken(email: 'user@example.com');

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

        $this->entityManager->clear();
        $subscription = $this->entityManager
            ->getRepository(Subscription::class)
            ->findOneBy(['carId' => $carId->getValue()]);
        $this->assertNotNull(actual: $subscription);

        // Test overlapping subscription prevention
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

        // Test non-overlapping slot works
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

    public function testOverlapValidationErrorDoesNotTriggerCompensation(): void
    {
        $carId = CarId::generate();
        
        $this->carApiMock->method('lockAndValidateCar')->willReturn(new CarDto(
            id: $carId->getValue(),
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000
        ));

        // Expect unlockCar to NEVER be called because the overlap fails BEFORE the lock
        $this->carApiMock->expects($this->never())
            ->method('unlockCar');

        $token = $this->getAuthToken(email: 'user@example.com');

        // Create a first subscription
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

        $this->assertSame(
            expected: Response::HTTP_CREATED,
            actual: $this->client->getResponse()->getStatusCode()
        );

        // Create overlapping subscription to trigger compensation
        $this->client->request(
            method: 'POST',
            uri: '/api/subscriptions',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            ],
            content: (string) json_encode(value: [
                'carId' => $carId->getValue(),
                'startDate' => '2026-06-05 12:00:00',
                'endDate' => '2026-06-12 12:00:00',
            ])
        );

        $this->assertSame(
            expected: Response::HTTP_UNPROCESSABLE_ENTITY,
            actual: $this->client->getResponse()->getStatusCode()
        );
    }

    public function testValidationErrors(): void
    {
        $token = $this->getAuthToken(email: 'user@example.com');

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
        $this->carApiMock->method('lockAndValidateCar')->willThrowException(new InvalidArgumentException(message: 'Car not found.'));

        $token = $this->getAuthToken(email: 'user@example.com');

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
        $this->carApiMock->method('lockAndValidateCar')->willThrowException(new DomainException(message: 'Car is already booked/unavailable.'));

        $token = $this->getAuthToken(email: 'user@example.com');

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
            expected: 'Car is already booked/unavailable.',
            actual: $data['detail']
        );
    }

    private function getAuthToken(string $email): string
    {
        $container = static::getContainer();
        $jwtManager = $container->get(id: 'lexik_jwt_authentication.jwt_manager');
        $user = new SecurityUser(user: new User(
            id: \App\Shared\Domain\ValueObject\UserId::generate(),
            email: new Email(value: $email),
            password: ''
        ));
        return $jwtManager->create(user: $user);
    }

    public function testListUserSubscriptions(): void
    {
        $token = $this->getAuthToken(email: 'list-user@example.com');

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

        $token = $this->getAuthToken(email: 'cancel-user@example.com');

        $this->carApiMock->method('lockAndValidateCar')->willReturn(new CarDto(
            id: $carId->getValue(),
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000
        ));

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

        $this->entityManager->clear();
        $subscription = $this->entityManager
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

        $token = $this->getAuthToken(email: 'checkout-user@example.com');

        $this->carApiMock->method('lockAndValidateCar')->willReturn(new CarDto(
            id: $carId->getValue(),
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000
        ));
        $this->carApiMock->method('getCarDetails')->willReturn(new CarDto(
            id: $carId->getValue(),
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000
        ));

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
