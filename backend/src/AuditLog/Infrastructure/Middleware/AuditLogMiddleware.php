<?php

declare(strict_types=1);

namespace App\AuditLog\Infrastructure\Middleware;

use App\AuditLog\Application\Command\CreateAuditLog\CreateAuditLogCommand;
use App\Shared\Domain\Event\AuditableEventInterface;
use App\User\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use DateTimeImmutable;

class AuditLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly Security $security
    ) {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $envelope = $stack->next()->handle(envelope: $envelope, stack: $stack);

        $message = $envelope->getMessage();

        if ($message instanceof AuditableEventInterface) {
            $this->commandBus->dispatch(message: new CreateAuditLogCommand(
                eventType: $message->getEventType(),
                aggregateId: $message->getAggregateId(),
                aggregateType: $message->getAggregateType(),
                payload: $message->getAuditPayload(),
                userId: $this->getCurrentUserId(),
                occurredAt: new DateTimeImmutable()
            ));
        }

        return $envelope;
    }

    private function getCurrentUserId(): ?string
    {
        $currentUser = $this->security->getUser();
        if ($currentUser instanceof SecurityUser) {
            return $currentUser->getUser()->getId()->getValue();
        }

        return null;
    }
}
