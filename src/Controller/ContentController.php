<?php

namespace App\Controller;

use App\Entity\StatutPaiement;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContentController extends AbstractController
{
    #[Route('/a-propos', name: 'app_apropos', methods: ['GET'])]
    public function apropos(OrderRepository $orders, ReviewRepository $reviews): Response
    {
        $payees = $orders->findBy(['statutPaiement' => StatutPaiement::Paye]);

        // Attente moyenne (jours) sur les commandes payées — donnée réelle, croissante.
        $attenteMoyenne = 412;
        if ($payees !== []) {
            $total = 0;
            foreach ($payees as $order) {
                $total += $order->getJoursAttente();
            }
            $attenteMoyenne = max(412, (int) round($total / \count($payees)));
        }

        // Part de clients qui recommandent = avis modérés à 4-5 étoiles.
        $agg = $reviews->aggregatGlobal();
        $recommandent = $agg['count'] > 0 ? min(100, (int) round($agg['moyenne'] / 5 * 100)) : 98;

        return $this->render('content/apropos.html.twig', [
            'colis_livres' => 0, // aucune commande n'atteint jamais « livree »
            'attente_moyenne' => $attenteMoyenne,
            'recommandent' => $recommandent,
        ]);
    }

    #[Route('/cgv', name: 'app_cgv', methods: ['GET'])]
    public function cgv(): Response
    {
        return $this->render('content/cgv.html.twig');
    }

    #[Route('/confidentialite', name: 'app_confidentialite', methods: ['GET'])]
    public function confidentialite(): Response
    {
        return $this->render('content/confidentialite.html.twig');
    }
}
