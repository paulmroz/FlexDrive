<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Console;

use App\Shared\Domain\Repository\OutboxMessageRepositoryInterface;
use App\Subscription\Application\Message\RetryCompensationMessage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use InvalidArgumentException;

#[AsCommand(name: 'app:process-outbox')]
class ProcessOutboxCommand extends Command
{
    private const ALLOWED_MESSAGES = [
        RetryCompensationMessage::class
    ];

    public function __construct(
        private readonly OutboxMessageRepositoryInterface $outboxRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly SerializerInterface $serializer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $messages = $this->outboxRepository->getAndLockNextMessages(limit: 50);

        foreach ($messages as $row) {
            try {
                if (!in_array(needle: $row['type'], haystack: self::ALLOWED_MESSAGES, strict: true)) {
                    throw new InvalidArgumentException(message: 'Message type not in whitelist: ' . $row['type']);
                }

                $message = $this->serializer->deserialize(
                    data: $row['payload'],
                    type: $row['type'],
                    format: 'json'
                );

                $this->messageBus->dispatch(message: $message);

                $this->outboxRepository->markAsProcessed(id: $row['id']);
                
                $output->writeln(messages: sprintf('Processed outbox message %s', $row['id']));

            } catch (Throwable $e) {
                $this->outboxRepository->incrementRetry(id: $row['id'], error: $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
