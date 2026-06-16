<?php

declare(strict_types=1);

namespace App\Subscription\Application\Message;

use App\Car\Api\CarApiInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RetryCompensationMessageHandler
{
    public function __construct(
        private readonly CarApiInterface $carApi
    ) {
    }

    public function __invoke(RetryCompensationMessage $message): void
    {
        $this->carApi->unlockCar(carId: $message->carId, sagaId: $message->sagaId);
    }
}
