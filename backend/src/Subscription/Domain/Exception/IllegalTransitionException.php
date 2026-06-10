<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Exception;

use DomainException;

class IllegalTransitionException extends DomainException
{
    public static function create(string $transitionName, string $currentStatus): self
    {
        return new self(message: sprintf('Cannot apply %s from current status "%s".', $transitionName, $currentStatus));
    }
}
