<?php

declare(strict_types=1);

namespace App\AuditLog\Tests\Unit;

use App\AuditLog\Application\Command\CreateAuditLog\CreateAuditLogCommand;
use App\AuditLog\Infrastructure\Middleware\AuditLogMiddleware;
use App\Shared\Domain\Event\AuditableEventInterface;
use App\Subscription\Domain\Event\PaymentCompletedEvent;
use App\Subscription\Domain\Event\PaymentConflictDetectedEvent;
use App\Subscription\Domain\Event\PaymentDisputedEvent;
use App\Subscription\Domain\Event\PaymentFailedEvent;
use App\Subscription\Domain\Event\PaymentRefundedEvent;
use App\User\Domain\Entity\User;
use App\User\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use stdClass;

class AuditLogMiddlewareTest extends TestCase
{
    /**
     * @dataProvider provideAuditableEvents
     */
    public function testMiddlewareDispatchesAuditCommandForAuditableEvents(
        AuditableEventInterface $event,
        string $expectedEventType,
        string $expectedAggregateId,
        string $expectedAggregateType
    ): void {
        $commandBusMock = $this->createMock(originalClassName: MessageBusInterface::class);
        $securityMock = $this->createMock(originalClassName: Security::class);

        $userId = UserId::generate();
        $user = new User(
            id: $userId,
            email: new Email(value: 'audit@example.com'),
            password: 'securepassword123'
        );
        $securityUser = new SecurityUser(user: $user);

        $securityMock->expects($this->once())
            ->method('getUser')
            ->willReturn(value: $securityUser);

        $commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(callback: function (CreateAuditLogCommand $command) use ($userId, $expectedEventType, $expectedAggregateId, $expectedAggregateType): bool {
                return $expectedEventType === $command->eventType
                    && $expectedAggregateId === $command->aggregateId
                    && $expectedAggregateType === $command->aggregateType
                    && ['subscriptionId' => 'sub-888'] === $command->payload
                    && $userId->getValue() === $command->userId;
            }))
            ->willReturn(value: new Envelope(message: new stdClass()));

        $envelope = new Envelope(message: $event);

        $nextMiddleware = $this->createMock(originalClassName: StackInterface::class);
        $nextMiddleware->expects($this->once())
            ->method('next')
            ->willReturn(value: new PassThroughMiddleware());

        $middleware = new AuditLogMiddleware(
            commandBus: $commandBusMock,
            security: $securityMock
        );

        $middleware->handle(envelope: $envelope, stack: $nextMiddleware);
    }

    /**
     * @dataProvider provideAuditableEvents
     */
    public function testMiddlewareDispatchesAuditCommandWithoutAuthenticatedUser(
        AuditableEventInterface $event,
        string $expectedEventType,
        string $expectedAggregateId,
        string $expectedAggregateType
    ): void {
        $commandBusMock = $this->createMock(originalClassName: MessageBusInterface::class);
        $securityMock = $this->createMock(originalClassName: Security::class);

        $securityMock->expects($this->once())
            ->method('getUser')
            ->willReturn(value: null);

        $commandBusMock->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(callback: function (CreateAuditLogCommand $command) use ($expectedEventType, $expectedAggregateId, $expectedAggregateType): bool {
                return $expectedEventType === $command->eventType
                    && $expectedAggregateId === $command->aggregateId
                    && $expectedAggregateType === $command->aggregateType
                    && ['subscriptionId' => 'sub-888'] === $command->payload
                    && null === $command->userId;
            }))
            ->willReturn(value: new Envelope(message: new stdClass()));

        $envelope = new Envelope(message: $event);

        $nextMiddleware = $this->createMock(originalClassName: StackInterface::class);
        $nextMiddleware->expects($this->once())
            ->method('next')
            ->willReturn(value: new PassThroughMiddleware());

        $middleware = new AuditLogMiddleware(
            commandBus: $commandBusMock,
            security: $securityMock
        );

        $middleware->handle(envelope: $envelope, stack: $nextMiddleware);
    }

    public function testMiddlewareIgnoresNonAuditableMessages(): void
    {
        $commandBusMock = $this->createMock(originalClassName: MessageBusInterface::class);
        $securityMock = $this->createMock(originalClassName: Security::class);

        $commandBusMock->expects($this->never())
            ->method('dispatch');

        $securityMock->expects($this->never())
            ->method('getUser');

        $envelope = new Envelope(message: new stdClass());

        $nextMiddleware = $this->createMock(originalClassName: StackInterface::class);
        $nextMiddleware->expects($this->once())
            ->method('next')
            ->willReturn(value: new PassThroughMiddleware());

        $middleware = new AuditLogMiddleware(
            commandBus: $commandBusMock,
            security: $securityMock
        );

        $middleware->handle(envelope: $envelope, stack: $nextMiddleware);
    }

    /**
     * @return array<string, array{AuditableEventInterface, string, string, string}>
     */
    public function provideAuditableEvents(): array
    {
        return [
            'payment completed' => [
                new PaymentCompletedEvent(paymentId: 'pay-777', subscriptionId: 'sub-888'),
                'subscription.payment_completed',
                'pay-777',
                'Payment',
            ],
            'payment failed' => [
                new PaymentFailedEvent(paymentId: 'pay-777', subscriptionId: 'sub-888'),
                'subscription.payment_failed',
                'pay-777',
                'Payment',
            ],
            'payment refunded' => [
                new PaymentRefundedEvent(paymentId: 'pay-777', subscriptionId: 'sub-888'),
                'subscription.payment_refunded',
                'pay-777',
                'Payment',
            ],
            'payment disputed' => [
                new PaymentDisputedEvent(paymentId: 'pay-777', subscriptionId: 'sub-888'),
                'subscription.payment_disputed',
                'pay-777',
                'Payment',
            ],
            'payment conflict detected' => [
                new PaymentConflictDetectedEvent(paymentId: 'pay-777', subscriptionId: 'sub-888'),
                'subscription.payment_conflict_detected',
                'pay-777',
                'Payment',
            ],
        ];
    }
}
