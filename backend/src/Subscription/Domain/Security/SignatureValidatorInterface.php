<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Security;

interface SignatureValidatorInterface
{
    public function isValid(
        string $payload,
        string $signatureHeader,
        string $secret
    ): bool;
}
