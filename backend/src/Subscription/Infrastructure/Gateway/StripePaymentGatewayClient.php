<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Gateway;

use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\Gateway\StripeCheckoutSessionDto;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use RuntimeException;
use Throwable;

class StripePaymentGatewayClient implements PaymentGatewayClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $stripeSecretKey,
        private readonly string $stripeSuccessUrl,
        private readonly string $stripeCancelUrl
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

    public function createCheckoutSession(
        string $subscriptionId,
        string $carBrandAndModel,
        int $amount,
        string $currency
    ): StripeCheckoutSessionDto {
        $response = $this->httpClient->request(
            method: 'POST',
            url: 'https://api.stripe.com/v1/checkout/sessions',
            options: [
                'auth_bearer' => $this->stripeSecretKey,
                'body' => [
                    'success_url' => $this->stripeSuccessUrl,
                    'cancel_url' => $this->stripeCancelUrl,
                    'mode' => 'payment',
                    'line_items[0][price_data][currency]' => $currency,
                    'line_items[0][price_data][product_data][name]' => $carBrandAndModel,
                    'line_items[0][price_data][unit_amount]' => (string) $amount,
                    'line_items[0][quantity]' => '1',
                    'metadata[subscription_id]' => $subscriptionId,
                ],
            ]
        );

        if (200 !== $response->getStatusCode() && 201 !== $response->getStatusCode()) {
            $errorMessage = 'Failed to create Stripe checkout session.';
            try {
                $errorData = $response->toArray(throw: false);
                if (isset($errorData['error']['message'])) {
                    $errorMessage .= ' Stripe Error: ' . $errorData['error']['message'];
                }
            } catch (Throwable) {
            }
            throw new RuntimeException(message: $errorMessage);
        }

        $data = $response->toArray();
        if (!isset($data['id']) || !isset($data['url'])) {
            throw new RuntimeException(message: 'Invalid response from Stripe API.');
        }

        return new StripeCheckoutSessionDto(
            sessionId: $data['id'],
            url: $data['url']
        );
    }
}
