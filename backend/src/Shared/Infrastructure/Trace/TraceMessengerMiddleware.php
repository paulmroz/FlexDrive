<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Trace;

use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpReceivedStamp;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;

class TraceMessengerMiddleware implements MiddlewareInterface
{
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $receivedStamp = $envelope->last(stampFqcn: ReceivedStamp::class);
        if (null !== $receivedStamp) {
            $traceId = null;

            $amqpReceived = $envelope->last(stampFqcn: AmqpReceivedStamp::class);
            if (null !== $amqpReceived) {
                $headers = $amqpReceived->getAmqpEnvelope()->getHeaders();
                $traceId = $headers['x-trace-id'] ?? null;
            }

            if (null === $traceId) {
                $traceStamp = $envelope->last(stampFqcn: TraceStamp::class);
                if (null !== $traceStamp) {
                    $traceId = $traceStamp->traceId;
                }
            }

            if (null !== $traceId) {
                TraceContext::setTraceId(traceId: $traceId);
            }
        }

        $sentStamp = $envelope->last(stampFqcn: SentStamp::class);
        if (null === $sentStamp && null === $receivedStamp) {
            $traceId = TraceContext::getTraceId();
            
            if (null === $envelope->last(stampFqcn: TraceStamp::class)) {
                $envelope = $envelope->with(new TraceStamp(traceId: $traceId));
            }

            $amqpStamp = $envelope->last(stampFqcn: AmqpStamp::class);
            $attributes = $amqpStamp?->getAttributes() ?? [];
            $headers = $attributes['headers'] ?? [];
            $headers['x-trace-id'] = $traceId;
            $attributes['headers'] = $headers;

            $envelope = $envelope->with(new AmqpStamp(
                routingKey: $amqpStamp?->getRoutingKey(),
                flags: $amqpStamp?->getFlags() ?? (defined('AMQP_NOPARAM') ? \AMQP_NOPARAM : 0),
                attributes: $attributes
            ));
        }

        try {
            return $stack->next()->handle(envelope: $envelope, stack: $stack);
        } finally {
            if (null !== $receivedStamp) {
                TraceContext::clear();
            }
        }
    }
}
