<?php

namespace App\Workflow;

use App\Entity\Order;
use App\Mail\OrderMailer;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * À l'entrée dans l'état « expediee », prévient le client que son colis est parti —
 * un e-mail qui n'annonce, en réalité, que le début d'une longue absence.
 */
class OrderShipmentListener
{
    public function __construct(private readonly OrderMailer $mailer)
    {
    }

    #[AsEventListener(event: 'workflow.order.entered.expediee')]
    public function onExpediee(EnteredEvent $event): void
    {
        $subject = $event->getSubject();
        if ($subject instanceof Order) {
            $this->mailer->sendShipmentNotice($subject);
        }
    }
}
