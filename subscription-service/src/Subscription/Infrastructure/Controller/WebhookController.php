<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Controller;

use App\Subscription\Infrastructure\Gateway\PaymentGatewayWebhookStrategyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class WebhookController extends AbstractController
{
    private array $strategies = [];

    /**
     * @param iterable<PaymentGatewayWebhookStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator(tag: PaymentGatewayWebhookStrategyInterface::class)]
        iterable $strategies
    ) {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->getGatewayName()] = $strategy;
        }
    }

    #[Route(path: '/api/payments/webhook/{gateway}', name: 'api_payments_webhook', methods: ['POST'])]
    public function __invoke(string $gateway, Request $request): JsonResponse
    {
        $strategy = $this->strategies[$gateway] ?? null;

        if (null === $strategy) {
            throw new NotFoundHttpException(message: 'Unsupported payment gateway.');
        }

        return $strategy->handle(request: $request);
    }
}
