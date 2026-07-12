<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Controller;

use App\Subscription\Application\Query\ListUserSubscriptions\ListUserSubscriptionsQuery;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * @see ListUserSubscriptionsQuery
 */
class ListUserSubscriptionsController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $queryBus)
    {
        $this->messageBus = $queryBus;
    }

    #[Route(path: '/api/subscriptions', name: 'api_list_subscriptions', methods: ['GET'])]
    public function __invoke(
        #[CurrentUser] SecurityUser $securityUser
    ): JsonResponse {
        $user = $securityUser->getUser();

        /** @var array<mixed> $subscriptions */
        $subscriptions = $this->handle(message: new ListUserSubscriptionsQuery(userId: $user->getId()->getValue()));

        return new JsonResponse(data: $subscriptions, status: JsonResponse::HTTP_OK);
    }
}
