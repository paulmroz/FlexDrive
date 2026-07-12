<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Command;

use App\Subscription\Application\Command\CleanupAbandonedBookings\CleanupAbandonedBookingsCommand as ApplicationCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

#[AsCommand(
    name: 'app:cleanup-abandoned-bookings',
    description: 'Cancels 10-minute old pending payments and their subscriptions'
)]
class CleanupAbandonedBookingsCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $this->messageBus->dispatch(message: new ApplicationCommand());
            $output->writeln(messages: 'Abandoned bookings cleanup finished successfully.');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $output->writeln(messages: 'Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
