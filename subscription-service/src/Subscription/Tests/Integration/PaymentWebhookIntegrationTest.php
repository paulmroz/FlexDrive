<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Integration;

use App\Car\Domain\ValueObject\CarId;
use App\Subscription\Domain\Entity\Payment;
use App\Subscription\Domain\Entity\Subscription;
use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use App\Shared\Domain\ValueObject\UserId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

class PaymentWebhookIntegrationTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private $paymentGatewayMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $container = static::getContainer();

        $this->entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(sql: 'TRUNCATE TABLE "subscriptions" CASCADE');
        $connection->executeStatement(sql: 'TRUNCATE TABLE "payments" CASCADE');
        $connection->executeStatement(sql: 'TRUNCATE TABLE "processed_webhooks" CASCADE');

        $this->paymentGatewayMock = $this->createMock(originalClassName: PaymentGatewayClientInterface::class);
        $container->set(id: PaymentGatewayClientInterface::class, service: $this->paymentGatewayMock);
    }

    public function testWebhookRejectsMissingSignature(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            content: (string) json_encode(value: [])
        );

        $this->assertSame(
            expected: Response::HTTP_BAD_REQUEST,
            actual: $this->client->getResponse()->getStatusCode()
        );
    }

    public function testWebhookRejectsInvalidSignature(): void
    {
        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => 't=123,v1=invalid'],
            content: (string) json_encode(value: [])
        );

        $this->assertSame(
            expected: Response::HTTP_FORBIDDEN,
            actual: $this->client->getResponse()->getStatusCode()
        );
    }

    public function testWebhookValidatesSignatureAndConfirmsPayment(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::PENDING_PAYMENT
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_alice999',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::CREATED
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->paymentGatewayMock->expects($this->never())->method('refund');

        $payload = [
            'id' => 'evt_alice_pay_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_alice999',
                    'amount_total' => 21000,
                    'currency' => 'pln',
                    'payment_intent' => 'pi_alice_intent_555',
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        if ($this->client->getResponse()->getStatusCode() !== Response::HTTP_OK) {
            fwrite(STDERR, $this->client->getResponse()->getContent() . "\n");
        }
        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $updatedSubscription = $this->entityManager->find(className: Subscription::class, id: $subscriptionId->getValue());

        $this->assertSame(expected: PaymentStatus::PAID, actual: $updatedPayment->getStatus());
        $this->assertSame(expected: SubscriptionStatus::ACTIVE, actual: $updatedSubscription->getStatus());
    }

    public function testWebhookDetectsPriceMismatchAndRefunds(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::PENDING_PAYMENT
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_alice999',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::CREATED
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $this->paymentGatewayMock->expects($this->once())
            ->method('refund')
            ->with('pi_alice_intent_555');

        $payload = [
            'id' => 'evt_alice_pay_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_alice999',
                    'amount_total' => 5000,
                    'currency' => 'pln',
                    'payment_intent' => 'pi_alice_intent_555',
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $this->assertSame(expected: PaymentStatus::PAID_CONFLICT, actual: $updatedPayment->getStatus());
    }

    public function testCleanupCommandExpiresAbandonedPayments(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::PENDING_PAYMENT
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_expired',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::CREATED
        );
        $this->entityManager->persist($payment);

        $this->entityManager->flush();
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement(
            sql: 'UPDATE payments SET created_at = :date WHERE id = :id',
            params: [
                'date' => (new DateTimeImmutable(datetime: '-15 minutes'))->format(format: 'Y-m-d H:i:s'),
                'id' => $payment->getId(),
            ]
        );

        $application = new Application(kernel: static::$kernel);
        $command = $application->find(name: 'app:cleanup-abandoned-bookings');
        $commandTester = new CommandTester(command: $command);
        $commandTester->execute(input: []);

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $updatedSubscription = $this->entityManager->find(className: Subscription::class, id: $subscriptionId->getValue());

        $this->assertSame(expected: PaymentStatus::FAILED, actual: $updatedPayment->getStatus());
        $this->assertSame(expected: SubscriptionStatus::CANCELLED, actual: $updatedSubscription->getStatus());
    }

    public function testWebhookHandlesRefund(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::ACTIVE
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_alice999',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::PAID
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $payload = [
            'id' => 'evt_alice_refund_001',
            'type' => 'charge.refunded',
            'data' => [
                'object' => [
                    'metadata' => [
                        'payment_id' => $payment->getId(),
                    ],
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $updatedSubscription = $this->entityManager->find(className: Subscription::class, id: $subscriptionId->getValue());

        $this->assertSame(expected: PaymentStatus::REFUNDED, actual: $updatedPayment->getStatus());
        $this->assertSame(expected: SubscriptionStatus::CANCELLED, actual: $updatedSubscription->getStatus());
    }

    public function testWebhookHandlesDispute(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::ACTIVE
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_alice999',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::PAID
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $payload = [
            'id' => 'evt_alice_dispute_001',
            'type' => 'charge.dispute.created',
            'data' => [
                'object' => [
                    'metadata' => [
                        'payment_id' => $payment->getId(),
                    ],
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $updatedSubscription = $this->entityManager->find(className: Subscription::class, id: $subscriptionId->getValue());

        $this->assertSame(expected: PaymentStatus::DISPUTED, actual: $updatedPayment->getStatus());
        $this->assertSame(expected: SubscriptionStatus::CANCELLED, actual: $updatedSubscription->getStatus());
    }

    public function testWebhookLatePaymentReactivatesSubscriptionWhenSlotIsFree(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::CANCELLED
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_late_payment',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::CREATED
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $payload = [
            'id' => 'evt_late_payment_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_late_payment',
                    'amount_total' => 21000,
                    'currency' => 'pln',
                    'payment_intent' => 'pi_late_payment_intent',
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $updatedSubscription = $this->entityManager->find(className: Subscription::class, id: $subscriptionId->getValue());

        $this->assertSame(expected: PaymentStatus::PAID, actual: $updatedPayment->getStatus());
        $this->assertSame(expected: SubscriptionStatus::ACTIVE, actual: $updatedSubscription->getStatus());
    }

    public function testWebhookLatePaymentRefundsWhenSlotIsOccupied(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::CANCELLED
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_late_payment_conflict',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::CREATED
        );
        $this->entityManager->persist($payment);

        $overlappingSubscriptionId = SubscriptionId::generate();
        $overlappingSubscription = new Subscription(
            id: $overlappingSubscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::ACTIVE
        );
        $this->entityManager->persist($overlappingSubscription);

        $this->entityManager->flush();

        $this->paymentGatewayMock->expects($this->once())
            ->method('refund')
            ->with('pi_late_payment_conflict_intent');

        $payload = [
            'id' => 'evt_late_payment_conflict_001',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_late_payment_conflict',
                    'amount_total' => 21000,
                    'currency' => 'pln',
                    'payment_intent' => 'pi_late_payment_conflict_intent',
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->entityManager->clear();
        $updatedPayment = $this->entityManager->find(className: Payment::class, id: $payment->getId());
        $updatedSubscription = $this->entityManager->find(className: Subscription::class, id: $subscriptionId->getValue());

        $this->assertSame(expected: PaymentStatus::PAID_CONFLICT, actual: $updatedPayment->getStatus());
        $this->assertSame(expected: SubscriptionStatus::CANCELLED, actual: $updatedSubscription->getStatus());
    }

    public function testWebhookIdempotencyPreventsDuplicateProcessing(): void
    {
        $carId = CarId::generate();

        $subscriptionId = SubscriptionId::generate();
        $subscription = new Subscription(
            id: $subscriptionId,
            userId: new UserId(value: Uuid::v4()->toString()),
            carId: $carId,
            startDate: new DateTimeImmutable(),
            status: SubscriptionStatus::PENDING_PAYMENT
        );
        $this->entityManager->persist($subscription);

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: 'cs_test_idempotent',
            subscriptionId: $subscriptionId->getValue(),
            amount: 21000,
            currency: 'PLN',
            status: PaymentStatus::CREATED
        );
        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        $payload = [
            'id' => 'evt_duplicate_id_999',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_idempotent',
                    'amount_total' => 21000,
                    'currency' => 'pln',
                    'payment_intent' => 'pi_idempotent_intent',
                ],
            ],
        ];

        $signatureHeader = $this->generateStripeSignature(payload: $payload);

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );

        $this->client->request(
            method: 'POST',
            uri: '/api/payments/webhook/stripe',
            server: ['HTTP_Stripe_Signature' => $signatureHeader],
            content: (string) json_encode(value: $payload)
        );

        $this->assertSame(
            expected: Response::HTTP_OK,
            actual: $this->client->getResponse()->getStatusCode()
        );
    }

    private function generateStripeSignature(array $payload): string
    {
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? 'whsec_dummy';
        $timestamp = time();
        $rawPayload = (string) json_encode(value: $payload);

        $signature = hash_hmac(
            algo: 'sha256',
            data: $timestamp . '.' . $rawPayload,
            key: $secret
        );

        return 't=' . $timestamp . ',v1=' . $signature;
    }
}
