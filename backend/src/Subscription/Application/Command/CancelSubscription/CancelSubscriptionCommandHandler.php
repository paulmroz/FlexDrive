<?php

declare(strict_types=1);

namespace App\Subscription\Application\Command\CancelSubscription;

use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\Transition\CancelTransition;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use InvalidArgumentException;
use DomainException;
use DateTimeImmutable;

/**
 * @see CancelSubscriptionCommand
 */
#[AsMessageHandler]
class CancelSubscriptionCommandHandler
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository
    ) {
    }

    public function __invoke(CancelSubscriptionCommand $command): void
    {
        $subscriptionId = new SubscriptionId(value: $command->subscriptionId);
        $subscription = $this->subscriptionRepository->findById(id: $subscriptionId);

        if (null === $subscription) {
            throw new InvalidArgumentException(message: 'Subscription not found.');
        }

        if ($subscription->getUserId()->getValue() !== $command->userId) {
            throw new DomainException(message: 'You are not authorized to cancel this subscription.');
        }

        $subscription->applyTransition(transition: new CancelTransition(cancelledAt: new DateTimeImmutable()));

        $this->subscriptionRepository->save(subscription: $subscription);
    }
}
