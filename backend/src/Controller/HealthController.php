<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function check(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
            $databaseStatus = 'OK';
        } catch (Exception $e) {
            $databaseStatus = 'ERROR';
        }

        return new JsonResponse([
            'status' => 'UP',
            'database' => $databaseStatus,
            'timestamp' => time(),
        ]);
    }
}
