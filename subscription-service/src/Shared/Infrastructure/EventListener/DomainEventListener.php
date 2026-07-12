<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventListener;

use App\Shared\Domain\AggregateRoot;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
class DomainEventListener
{
    public function __construct(
        private readonly MessageBusInterface $eventBus
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->dispatchEventsForEntity(entity: $args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->dispatchEventsForEntity(entity: $args->getObject());
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $this->dispatchEventsForEntity(entity: $args->getObject());
    }

    private function dispatchEventsForEntity(object $entity): void
    {
        if (!$entity instanceof AggregateRoot) {
            return;
        }

        foreach ($entity->pullDomainEvents() as $event) {
            $this->eventBus->dispatch(message: $event);
        }
    }
}
