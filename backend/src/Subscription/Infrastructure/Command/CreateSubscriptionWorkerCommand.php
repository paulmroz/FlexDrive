<?php

declare(strict_types=1);

namespace App\Subscription\Infrastructure\Command;

use App\Subscription\Application\Command\CreateSubscription\CreateSubscriptionCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;
use DateTimeImmutable;
use Throwable;

#[AsCommand(
    name: 'app:create-subscription-worker',
    description: 'Internal worker to test concurrent subscription creations'
)]
class CreateSubscriptionWorkerCommand extends Command
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                name: 'carId',
                mode: InputArgument::REQUIRED,
                description: 'Car UUID'
            )
            ->addArgument(
                name: 'userId',
                mode: InputArgument::REQUIRED,
                description: 'User UUID'
            )
            ->addArgument(
                name: 'startDate',
                mode: InputArgument::REQUIRED,
                description: 'Start Date (Y-m-d H:i:s)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $carId = (string) $input->getArgument(name: 'carId');
        $userId = (string) $input->getArgument(name: 'userId');
        $startDate = new DateTimeImmutable(datetime: (string) $input->getArgument(name: 'startDate'));

        try {
            $this->messageBus->dispatch(message: new CreateSubscriptionCommand(
                subscriptionId: Uuid::v4()->toString(),
                userId: $userId,
                carId: $carId,
                startDate: $startDate,
                endDate: null
            ));
            $output->writeln(messages: 'SUCCESS');
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $previous = null === $e->getPrevious() ? $e : $e->getPrevious();
            $output->writeln(messages: 'ERROR: ' . $previous->getMessage());
            return Command::FAILURE;
        }
    }
}
