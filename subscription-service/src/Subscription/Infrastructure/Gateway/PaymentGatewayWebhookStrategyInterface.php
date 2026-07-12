<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Gateway;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface PaymentGatewayWebhookStrategyInterface
{
    public function getGatewayName(): string;

    public function handle(Request $request): JsonResponse;
}
