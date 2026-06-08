<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Controller;

use App\Car\Api\CarApiInterface;
use App\Subscription\Domain\Entity\Payment;
use App\Subscription\Domain\Gateway\PaymentGatewayClientInterface;
use App\Subscription\Domain\Repository\PaymentRepositoryInterface;
use App\Subscription\Domain\Repository\SubscriptionRepositoryInterface;
use App\Subscription\Domain\ValueObject\PaymentStatus;
use App\Subscription\Domain\ValueObject\SubscriptionId;
use App\Subscription\Domain\ValueObject\SubscriptionStatus;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

class CreateCheckoutSessionController extends AbstractController
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository,
        private readonly CarApiInterface $carApi,
        private readonly PaymentGatewayClientInterface $paymentGatewayClient,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
    }

    #[Route(path: '/api/subscriptions/{id}/checkout', name: 'api_create_checkout_session', methods: ['POST'])]
    public function __invoke(
        string $id,
        #[CurrentUser] SecurityUser $securityUser
    ): JsonResponse {
        $user = $securityUser->getUser();
        $subscription = $this->subscriptionRepository->findById(id: new SubscriptionId(value: $id));

        if (null === $subscription) {
            throw new NotFoundHttpException(message: 'Subscription not found.');
        }

        if ($subscription->getUserId()->getValue() !== $user->getId()->getValue()) {
            throw new AccessDeniedHttpException(message: 'You do not own this subscription.');
        }

        if (SubscriptionStatus::PENDING_PAYMENT !== $subscription->getStatus()) {
            throw new BadRequestHttpException(message: 'Subscription is not pending payment.');
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
            subscriptionId: $id,
            carBrandAndModel: $carDto->brand . ' ' . $carDto->model,
            amount: $amount,
            currency: 'usd',
            successUrl: 'http://localhost:5173/dashboard',
            cancelUrl: 'http://localhost:5173/dashboard'
        );

        $payment = new Payment(
            id: Uuid::v4()->toString(),
            sessionId: $sessionDto->sessionId,
            subscriptionId: $id,
            amount: $amount,
            currency: 'usd',
            status: PaymentStatus::CREATED
        );

        $this->paymentRepository->save(payment: $payment);

        return new JsonResponse(
            data: [
                'paymentUrl' => $sessionDto->url
            ],
            status: Response::HTTP_OK
        );
    }
}
