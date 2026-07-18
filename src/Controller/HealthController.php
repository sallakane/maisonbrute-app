<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class HealthController extends AbstractController
{
    /**
     * Sonde de santé pour le déploiement et le healthcheck du conteneur.
     * Vérifie que l'app répond et que la base est joignable.
     */
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function health(Connection $connection): JsonResponse
    {
        try {
            $connection->executeQuery('SELECT 1');
            $db = 'ok';
        } catch (\Throwable) {
            $db = 'ko';
        }

        return new JsonResponse(
            ['status' => $db === 'ok' ? 'ok' : 'degraded', 'db' => $db],
            $db === 'ok' ? 200 : 503,
        );
    }
}
