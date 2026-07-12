<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Controller;

use App\Subscription\Application\Command\CancelSubscription\CancelSubscriptionCommand;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * @see CancelSubscriptionCommand
 */
class CancelSubscriptionController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route(path: '/api/subscriptions/{id}/cancel', name: 'api_cancel_subscription', methods: ['POST'])]
    public function __invoke(
        string $id,
        #[CurrentUser] SecurityUser $securityUser
    ): JsonResponse {
        $user = $securityUser->getUser();

        $this->messageBus->dispatch(message: new CancelSubscriptionCommand(
            subscriptionId: $id,
            userId: $user->getId()->getValue()
        ));

        return new JsonResponse(
            data: ['message' => 'Subscription cancelled successfully.'],
            status: JsonResponse::HTTP_OK
        );
    }
}
