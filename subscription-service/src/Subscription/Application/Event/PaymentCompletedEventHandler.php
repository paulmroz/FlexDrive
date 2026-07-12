<?php

declare(strict_types=1);

namespace App\Subscription\Application\Event;

use App\Subscription\Domain\Event\PaymentCompletedEvent;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PaymentCompletedEventHandler
{
    public function __invoke(PaymentCompletedEvent $event): void
    {
    }
}
