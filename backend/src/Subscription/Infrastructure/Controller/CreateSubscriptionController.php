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
use DateTimeImmutable;

/**
 * @see CreateSubscriptionCommand
 */
class CreateSubscriptionController extends AbstractController
{
    #[Route(path: '/api/subscriptions', name: 'api_create_subscription', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] CreateSubscriptionRequest $request,
        #[CurrentUser] SecurityUser $securityUser,
        MessageBusInterface $messageBus
    ): JsonResponse {
        $user = $securityUser->getUser();
        $startDate = new DateTimeImmutable(datetime: $request->startDate);
        $endDate = null !== $request->endDate ? new DateTimeImmutable(datetime: $request->endDate) : null;

        $messageBus->dispatch(message: new CreateSubscriptionCommand(
            userId: $user->getId()->getValue(),
            carId: $request->carId,
            startDate: $startDate,
            endDate: $endDate
        ));

        return new JsonResponse(
            data: ['message' => 'Subscription created successfully.'],
            status: JsonResponse::HTTP_CREATED
        );
    }
}
