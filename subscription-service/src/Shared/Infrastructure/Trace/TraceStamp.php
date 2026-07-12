<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Trace;

use Symfony\Component\Messenger\Stamp\StampInterface;

class TraceStamp implements StampInterface
{
    public function __construct(
        public readonly string $traceId
    ) {
    }
}
