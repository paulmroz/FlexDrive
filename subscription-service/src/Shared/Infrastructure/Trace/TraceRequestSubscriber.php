<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Trace;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TraceRequestSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 512],
            KernelEvents::RESPONSE => ['onResponse', -512],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $traceId = $request->headers->get(key: 'X-Trace-Id') ?? $request->headers->get(key: 'x-trace-id');

        if (null !== $traceId && '' !== $traceId) {
            TraceContext::setTraceId(traceId: $traceId);
        } else {
            TraceContext::getTraceId();
        }
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set(key: 'X-Trace-Id', values: TraceContext::getTraceId());
    }
}
