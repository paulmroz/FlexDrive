<?php

declare(strict_types=1);

namespace App\Shared\Tests\Unit\Trace;

use App\Shared\Infrastructure\Trace\TraceContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class TraceContextTest extends TestCase
{
    protected function tearDown(): void
    {
        TraceContext::clear();
    }

    public function testGetTraceIdGeneratesNewUuidIfNoneSet(): void
    {
        $traceId = TraceContext::getTraceId();
        
        $this->assertNotEmpty(actual: $traceId);
        $this->assertTrue(condition: Uuid::isValid(uuid: $traceId));
    }

    public function testGetTraceIdReturnsSetTraceId(): void
    {
        $customId = 'req-12345';
        TraceContext::setTraceId(traceId: $customId);
        
        $this->assertSame(expected: $customId, actual: TraceContext::getTraceId());
    }

    public function testClearRemovesTraceId(): void
    {
        TraceContext::setTraceId(traceId: 'req-12345');
        TraceContext::clear();
        
        $newTraceId = TraceContext::getTraceId();
        $this->assertNotSame(expected: 'req-12345', actual: $newTraceId);
    }
}
