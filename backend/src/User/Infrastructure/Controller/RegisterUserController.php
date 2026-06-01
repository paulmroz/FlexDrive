<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Infrastructure\Request\RegisterUserRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see RegisterUserCommand
 */
class RegisterUserController extends AbstractController
{
    #[Route(path: '/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RegisterUserRequest $request,
        MessageBusInterface $messageBus
    ): JsonResponse {
        try {
            $messageBus->dispatch(message: new RegisterUserCommand(
                email: $request->email,
                password: $request->password
            ));
        } catch (HandlerFailedException $e) {
            $originalException = $e->getPrevious() ?? $e;
            return new JsonResponse(
                data: ['error' => $originalException->getMessage()],
                status: JsonResponse::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return new JsonResponse(
            data: ['message' => 'User registered successfully.'],
            status: JsonResponse::HTTP_CREATED
        );
    }
}
