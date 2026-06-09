<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CreateCheckoutSession;

use App\Car\Api\CarApiInterface;
use App\Subscription\Application\Command\CreateCheckoutSession\CreateCheckoutSessionCommand;
use App\Subscription\Domain\Entity\Payment;
use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\Repository\PaymentRepositoryInterface;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use InvalidArgumentException;
use DomainException;

#[AsMessageHandler]
class CreateCheckoutSessionCommandHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly CarApiInterface $carApi,
        private readonly PaymentGatewayClientInterface $paymentGatewayClient,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
    }

    public function __invoke(CreateCheckoutSessionCommand $command): string
    {
        $subscription = $this->subscriptionRepository->findById(id: new SubscriptionId(value: $command->subscriptionId));

        if (null === $subscription) {
            throw new InvalidArgumentException(message: 'Subscription not found.');
        }

        if ($subscription->getUserId()->getValue() !== $command->userId) {
            throw new DomainException(message: 'You do not own this subscription.');
        }

        if (SubscriptionStatus::PENDING_PAYMENT !== $subscription->getStatus()) {
            throw new DomainException(message: 'Subscription is not pending payment.');
        }

        $carDto = $this->carApi->getCarDetails(carId: $subscription->getCarId()->getValue());

        $startDate = $subscription->getStartDate();
        $endDate = $subscription->getEndDate();

        $days = 1;
        if (null !== $endDate) {
            $diff = $startDate->diff(targetObject: $endDate);
            $days = $diff->days > 1 ? $diff->days : 1;
        }

        $amount = $carDto->pricePerDay * $days;

        $sessionDto = $this->paymentGatewayClient->createCheckoutSession(
            subscriptionId: $command->subscriptionId,
            carBrandAndModel: $carDto->brand . ' ' . $carDto->model,
            amount: $amount,
            currency: 'usd',
            successUrl: 'http://localhost:5173/dashboard',
            cancelUrl: 'http://localhost:5173/dashboard'
        );

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: $sessionDto->sessionId,
            subscriptionId: $command->subscriptionId,
            amount: $amount,
            currency: 'usd',
            status: PaymentStatus::CREATED
        );

        $this->paymentRepository->save(payment: $payment);

        return $sessionDto->url;
    }
}
