<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Trace;

use Symfony\Component\Uid\Uuid;

class TraceContext
{
    private static ?string $traceId = null;

    public static function getTraceId(): string
    {
        if (self::$traceId === null) {
            self::$traceId = Uuid::v4()->toString();
        }

        return self::$traceId;
    }

    public static function setTraceId(string $traceId): void
    {
        self::$traceId = $traceId;
    }

    public static function clear(): void
    {
        self::$traceId = null;
    }
}
