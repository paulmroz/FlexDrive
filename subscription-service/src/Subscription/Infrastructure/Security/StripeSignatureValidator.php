<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Security;

use App\Subscription\Domain\Security\SignatureValidatorInterface;

class StripeSignatureValidator implements SignatureValidatorInterface
{
    public function isValid(
        string $payload,
        string $signatureHeader,
        string $secret
    ): bool {
        $parts = explode(separator: ',', string: $signatureHeader);
        $timestamp = null;
        $signature = null;

        foreach ($parts as $part) {
            $pair = explode(separator: '=', string: $part);
            if (2 !== count($pair)) {
                continue;
            }
            if ('t' === $pair[0]) {
                $timestamp = $pair[1];
            } elseif ('v1' === $pair[0]) {
                $signature = $pair[1];
            }
        }

        if (null === $timestamp || null === $signature) {
            return false;
        }

        $timestampInt = (int) $timestamp;

        if (abs(num: time() - $timestampInt) > 300) {
            return false;
        }

        $expectedSignature = hash_hmac(
            algo: 'sha256',
            data: $timestamp . '.' . $payload,
            key: $secret
        );

        return hash_equals(known_string: $signature, user_string: $expectedSignature);
    }
}
