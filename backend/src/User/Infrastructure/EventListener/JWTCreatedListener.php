<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use App\User\Infrastructure\Security\SecurityUser;

class JWTCreatedListener
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof SecurityUser) {
            return;
        }

        $payload = $event->getData();
        $payload['id'] = $user->getUser()->getId()->getValue();

        $event->setData(data: $payload);
    }
}
