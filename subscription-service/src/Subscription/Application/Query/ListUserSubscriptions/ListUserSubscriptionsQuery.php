<?php

declare(strict_types=1);

namespace App\Subscription\Application\Query\ListUserSubscriptions;

use App\Subscription\Application\Query\ListUserSubscriptions\ListUserSubscriptionsQueryHandler;

/**
 * @see ListUserSubscriptionsQueryHandler
 */
class ListUserSubscriptionsQuery
{
    public function __construct(
        public readonly string $userId
    ) {
    }
}
