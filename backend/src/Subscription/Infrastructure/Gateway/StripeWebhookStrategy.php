<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Gateway;

use App\Subscription\Application\Command\ConfirmPayment\ConfirmPaymentCommand;
use App\Subscription\Application\Command\DisputePayment\DisputePaymentCommand;
use App\Subscription\Application\Command\RefundPayment\RefundPaymentCommand;
use App\Subscription\Domain\Security\SignatureValidatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\MessageBusInterface;

class StripeWebhookStrategy implements PaymentGatewayWebhookStrategyInterface
{
    public function __construct(
        private readonly SignatureValidatorInterface $signatureValidator,
        private readonly MessageBusInterface $messageBus,
        private readonly string $webhookSecret
    ) {
    }

    public function getGatewayName(): string
    {
        return 'stripe';
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signatureHeader = $request->headers->get(key: 'Stripe-Signature');

        if (null === $signatureHeader) {
            throw new BadRequestHttpException(message: 'Missing Stripe-Signature header.');
        }

        $isValid = $this->signatureValidator->isValid(
            payload: $payload,
            signatureHeader: $signatureHeader,
            secret: $this->webhookSecret
        );

        if (false === $isValid) {
            throw new AccessDeniedHttpException(message: 'Invalid signature.');
        }

        $event = json_decode(
            json: $payload,
            associative: true
        );

        if (null === $event || !isset($event['id']) || !isset($event['type']) || !isset($event['data']['object'])) {
            throw new BadRequestHttpException(message: 'Invalid payload.');
        }

        $this->dispatchCommand(event: $event);

        return new JsonResponse(data: ['status' => 'success']);
    }

    private function dispatchCommand(array $event): void
    {
        $eventType = $event['type'];
        $object = $event['data']['object'];

        $command = match ($eventType) {
            'checkout.session.completed' => new ConfirmPaymentCommand(
                webhookEventId: $event['id'],
                sessionId: $object['id'],
                amount: $object['amount_total'],
                currency: $object['currency'],
                transactionId: $object['payment_intent']
            ),
            'charge.refunded' => new RefundPaymentCommand(
                webhookEventId: $event['id'],
                paymentId: $object['metadata']['payment_id'] ?? null,
                sessionId: $object['metadata']['session_id'] ?? null
            ),
            'charge.dispute.created' => new DisputePaymentCommand(
                webhookEventId: $event['id'],
                paymentId: $object['metadata']['payment_id'] ?? null,
                sessionId: $object['metadata']['session_id'] ?? null
            ),
            default => null,
        };

        if (null !== $command) {
            $this->messageBus->dispatch(message: $command);
        }
    }
}
