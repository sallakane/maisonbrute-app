<?php

namespace App\Payment;

use App\Entity\Order;
use App\Entity\StatutPaiement;
use App\Mail\OrderMailer;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Encaisse (fictivement) une commande une fois le paiement Stripe confirmé.
 * Idempotent : rejouer l'événement (webhook renvoyé) ne fait rien de plus.
 * Source de vérité = le webhook, jamais le simple retour navigateur.
 */
class OrderPaymentFinalizer
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly WorkflowInterface $orderStateMachine,
        private readonly OrderMailer $mailer,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function markPaid(Order $order, ?string $paymentIntentId = null): bool
    {
        if ($order->isPaye()) {
            return false; // déjà traité
        }

        $order->setStatutPaiement(StatutPaiement::Paye);
        if ($paymentIntentId !== null) {
            $order->setStripePaymentIntentId($paymentIntentId);
        }

        if ($this->orderStateMachine->can($order, 'payer')) {
            $this->orderStateMachine->apply($order, 'payer');
        }

        $this->em->flush();

        $this->mailer->sendOrderConfirmation($order);
        $this->logger->info('Commande {ref} payée (test) et confirmée.', ['ref' => $order->getReference()]);

        return true;
    }
}
