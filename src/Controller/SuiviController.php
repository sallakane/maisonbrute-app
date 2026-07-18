<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SuiviController extends AbstractController
{
    #[Route('/suivi', name: 'app_suivi', methods: ['GET', 'POST'])]
    public function suivi(Request $request, OrderRepository $orders): Response
    {
        $reference = trim((string) $request->request->get('reference', $request->query->get('reference', '')));
        $email = trim((string) $request->request->get('email', ''));
        $order = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('suivi', (string) $request->request->get('_token'))) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $found = $reference !== '' ? $orders->findOneByReference($reference) : null;
            // Vérification e-mail : empêche l'énumération des commandes d'autrui.
            if ($found !== null && $email !== '' && strcasecmp((string) $found->getEmail(), $email) === 0) {
                $order = $found;
            } else {
                $this->addFlash('error', 'Aucune commande ne correspond à cette référence et cet e-mail. L\'attente, elle, reste disponible.');
            }
        }

        return $this->render('suivi/index.html.twig', [
            'order' => $order,
            'reference' => $reference,
        ]);
    }
}
