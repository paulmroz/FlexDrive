<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use DomainException;
use InvalidArgumentException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $environment
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onKernelException', 10],
            ],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof HandlerFailedException) {
            $exception = $exception->getPrevious() ?? $exception;
        }

        $request = $event->getRequest();

        $status = JsonResponse::HTTP_INTERNAL_SERVER_ERROR;
        $title = 'Internal Server Error';
        $detail = $exception->getMessage();
        $invalidParams = [];

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            $title = $this->getTitleForStatusCode(statusCode: $status);
            $previous = $exception->getPrevious();
            if ($previous instanceof ValidationFailedException) {
                $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
                $title = 'Validation Failed';
                $detail = 'One or more fields failed validation.';
                foreach ($previous->getViolations() as $violation) {
                    $invalidParams[] = [
                        'name' => $violation->getPropertyPath(),
                        'reason' => $violation->getMessage(),
                    ];
                }
            }
        } elseif ($exception instanceof DomainException || $exception instanceof InvalidArgumentException) {
            $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY;
            $title = 'Business Rule Violation';
        } elseif ($exception instanceof AuthenticationException) {
            $status = JsonResponse::HTTP_UNAUTHORIZED;
            $title = 'Unauthorized';
        } elseif ($exception instanceof AccessDeniedException || $exception instanceof AccessDeniedHttpException) {
            if (str_contains(haystack: $exception->getMessage(), needle: 'not appropriately authenticated')) {
                $status = JsonResponse::HTTP_UNAUTHORIZED;
                $title = 'Unauthorized';
            } else {
                $status = JsonResponse::HTTP_FORBIDDEN;
                $title = 'Forbidden';
            }
        }

        if (JsonResponse::HTTP_INTERNAL_SERVER_ERROR === $status && 'prod' === $this->environment) {
            $detail = 'An unexpected error occurred.';
        }

        $data = [
            'type' => 'https://tools.ietf.org/html/rfc7807',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => $request->getRequestUri(),
        ];

        if ([] !== $invalidParams) {
            $data['invalid_params'] = $invalidParams;
        }

        if ('prod' !== $this->environment && JsonResponse::HTTP_INTERNAL_SERVER_ERROR === $status) {
            $data['trace'] = $exception->getTraceAsString();
        }

        $response = new JsonResponse(data: $data, status: $status);
        $response->headers->set(key: 'Content-Type', values: 'application/problem+json');

        $event->setResponse($response);
    }

    private function getTitleForStatusCode(int $statusCode): string
    {
        return match ($statusCode) {
            JsonResponse::HTTP_NOT_FOUND => 'Resource Not Found',
            JsonResponse::HTTP_METHOD_NOT_ALLOWED => 'Method Not Allowed',
            JsonResponse::HTTP_FORBIDDEN => 'Forbidden',
            JsonResponse::HTTP_UNAUTHORIZED => 'Unauthorized',
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY => 'Validation Failed',
            default => 'HTTP Error',
        };
    }
}
