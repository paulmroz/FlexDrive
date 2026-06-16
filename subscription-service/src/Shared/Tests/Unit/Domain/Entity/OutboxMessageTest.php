<?php

declare(strict_types=1);

namespace App\Shared\Tests\Unit\Domain\Entity;

use App\Shared\Domain\Entity\OutboxMessage;
use PHPUnit\Framework\TestCase;

class OutboxMessageTest extends TestCase
{
    public function testMessageIsCreatedWithCorrectState(): void
    {
        $type = 'SomeMessageClass';
        $payload = '{"foo":"bar"}';

        $message = new OutboxMessage(type: $type, payload: $payload);

        // We use Reflection since properties are private and there are no getters yet
        $reflection = new \ReflectionClass(objectOrClass: $message);
        
        $idProperty = $reflection->getProperty(name: 'id');
        $idProperty->setAccessible(accessible: true);
        
        $typeProperty = $reflection->getProperty(name: 'type');
        $typeProperty->setAccessible(accessible: true);

        $payloadProperty = $reflection->getProperty(name: 'payload');
        $payloadProperty->setAccessible(accessible: true);

        $retryProperty = $reflection->getProperty(name: 'retryCount');
        $retryProperty->setAccessible(accessible: true);

        $processedProperty = $reflection->getProperty(name: 'processedAt');
        $processedProperty->setAccessible(accessible: true);

        $this->assertNotEmpty($idProperty->getValue(object: $message));
        $this->assertSame(expected: $type, actual: $typeProperty->getValue(object: $message));
        $this->assertSame(expected: $payload, actual: $payloadProperty->getValue(object: $message));
        $this->assertSame(expected: 0, actual: $retryProperty->getValue(object: $message));
        $this->assertNull(actual: $processedProperty->getValue(object: $message));
    }
}
