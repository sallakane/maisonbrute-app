<?php

namespace App\Tracking;

use App\Entity\Order;
use App\Entity\TrackingEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Fait « avancer » une commande payée d'un cran à chaque passage :
 *   payee → en_preparation → expediee → en_transit, puis, une fois en transit,
 *   ajoute indéfiniment un statut de dérive. Jamais de transition vers « livree ».
 */
class TrackingAdvancer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $orderStateMachine,
    ) {
    }

    /** @return bool true si un événement a été ajouté */
    public function advance(Order $order): bool
    {
        if (!$order->isPaye()) {
            return false;
        }

        $libelle = match (true) {
            $this->orderStateMachine->can($order, 'preparer') => $this->step($order, 'preparer', TrackingNarrative::PREPARATION),
            $this->orderStateMachine->can($order, 'expedier') => $this->step($order, 'expedier', TrackingNarrative::EXPEDITION),
            $this->orderStateMachine->can($order, 'convoyer') => $this->step($order, 'convoyer', TrackingNarrative::MISE_EN_TRANSIT),
            $order->isEnTransit() => TrackingNarrative::prochaineDerive($order->getDernierEvent()?->getLibelle()),
            default => null,
        };

        if ($libelle === null) {
            return false;
        }

        $order->addTrackingEvent(new TrackingEvent($order, $libelle));
        $this->em->flush();

        return true;
    }

    private function step(Order $order, string $transition, string $libelle): string
    {
        // L'application de la transition peut déclencher des listeners (e-mail d'expédition).
        $this->orderStateMachine->apply($order, $transition);

        return $libelle;
    }
}
