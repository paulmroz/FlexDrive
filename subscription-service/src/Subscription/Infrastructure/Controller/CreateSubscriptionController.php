<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Controller;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommand;
use App\Subscription\Infrastructure\Request\CreateSubscriptionRequest;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;

class CreateSubscriptionController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    #[Route(path: '/api/subscriptions', name: 'api_create_subscription', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateSubscriptionRequest $request,
        #[CurrentUser] SecurityUser $securityUser
    ): JsonResponse {
        $user = $securityUser->getUser();
        $subscriptionId = Uuid::v4()->toString();

        $startDate = new DateTimeImmutable(datetime: $request->startDate);
        $endDate = null !== $request->endDate ? new DateTimeImmutable(datetime: $request->endDate) : null;

        $this->messageBus->dispatch(message: new CreateSubscriptionCommand(
            subscriptionId: $subscriptionId,
            userId: $user->getId()->getValue(),
            carId: $request->carId,
            startDate: $startDate,
            endDate: $endDate
        ));

        return new JsonResponse(
            data: [
                'subscriptionId' => $subscriptionId
            ],
            status: JsonResponse::HTTP_CREATED
        );
    }
}
