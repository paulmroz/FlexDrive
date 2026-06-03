<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Gateway;

use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use RuntimeException;

class StripePaymentGatewayClient implements PaymentGatewayClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $stripeSecretKey
    ) {
    }

    public function refund(string $transactionId): void
    {
        $response = $this->httpClient->request(
            method: 'POST',
            url: 'https://api.stripe.com/v1/refunds',
            options: [
                'auth_bearer' => $this->stripeSecretKey,
                'body' => [
                    'payment_intent' => $transactionId,
                ],
            ]
        );

        if (200 !== $response->getStatusCode() && 201 !== $response->getStatusCode()) {
            throw new RuntimeException(message: 'Failed to issue refund via Stripe API.');
        }
    }
}
