<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Controller;

use App\Subscription\Application\Command\CreateCheckoutSession\CreateCheckoutSessionCommand;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * @see CreateCheckoutSessionCommand
 */
class CreateCheckoutSessionController extends AbstractController
{
    use HandleTrait;

    public function __construct(MessageBusInterface $commandBus)
    {
        $this->messageBus = $commandBus;
    }

    #[Route(path: '/api/subscriptions/{id}/checkout', name: 'api_create_checkout_session', methods: ['POST'])]
    public function __invoke(
        string $id,
        #[CurrentUser] SecurityUser $securityUser
    ): JsonResponse {
        $user = $securityUser->getUser();

        /** @var string $paymentUrl */
        $paymentUrl = $this->handle(message: new CreateCheckoutSessionCommand(
            subscriptionId: $id,
            userId: $user->getId()->getValue()
        ));

        return new JsonResponse(
            data: [
                'paymentUrl' => $paymentUrl
            ],
            status: Response::HTTP_OK
        );
    }
}
