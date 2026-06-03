<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Integration;

use App\Car\Domain\Entity\Car;
use App\Car\Domain\ValueObject\CarId;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\UserId;
use App\User\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SubscriptionConcurrencyTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(id: 'doctrine.orm.entity_manager');
        $connection = $this->entityManager->getConnection();

        $connection->executeStatement(sql: 'TRUNCATE TABLE "users" CASCADE');
        $connection->executeStatement(sql: 'TRUNCATE TABLE "cars" CASCADE');
        $connection->executeStatement(sql: 'TRUNCATE TABLE "subscriptions" CASCADE');
    }

    public function testConcurrentSubscriptionCreationLocksCarPessimistically(): void
    {
        $userId = UserId::generate();
        $user = new User(
            id: $userId,
            email: new Email(value: 'concurrency@example.com'),
            password: 'securepassword123'
        );
        $this->entityManager->persist($user);

        $carId = CarId::generate();
        $car = new Car(
            id: $carId,
            brand: 'Tesla',
            model: 'Model Y',
            pricePerDay: 3000,
            available: true
        );
        $this->entityManager->persist($car);
        $this->entityManager->flush();

        $cmd = 'php bin/console app:create-subscription-worker ' . $carId->getValue() . ' ' . $userId->getValue() . ' "2026-06-01 10:00:00" --env=test';

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes1 = [];
        $process1 = proc_open(
            command: $cmd,
            descriptor_spec: $descriptors,
            pipes: $pipes1
        );

        $pipes2 = [];
        $process2 = proc_open(
            command: $cmd,
            descriptor_spec: $descriptors,
            pipes: $pipes2
        );

        $this->assertIsResource($process1);
        $this->assertIsResource($process2);

        $output1 = stream_get_contents(stream: $pipes1[1]);
        $err1 = stream_get_contents(stream: $pipes1[2]);
        fclose(stream: $pipes1[0]);
        fclose(stream: $pipes1[1]);
        fclose(stream: $pipes1[2]);
        proc_close(process: $process1);

        $output2 = stream_get_contents(stream: $pipes2[1]);
        $err2 = stream_get_contents(stream: $pipes2[2]);
        fclose(stream: $pipes2[0]);
        fclose(stream: $pipes2[1]);
        fclose(stream: $pipes2[2]);
        $outputs = [
            trim(string: $output1),
            trim(string: $output2),
        ];

        $successCount = 0;
        $errorCount = 0;

        foreach ($outputs as $out) {
            if ('SUCCESS' === $out) {
                $successCount++;
            } elseif (str_contains(haystack: $out, needle: 'ERROR: Car is already booked/unavailable.')) {
                $errorCount++;
            }
        }


        $this->assertSame(expected: 1, actual: $successCount);
        $this->assertSame(expected: 1, actual: $errorCount);
    }
}
