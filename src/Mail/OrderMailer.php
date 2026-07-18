<?php

namespace App\Mail;

use App\Entity\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress,
    ) {
    }

    public function sendOrderConfirmation(Order $order): void
    {
        $to = $order->getEmail();
        if ($to === null) {
            return;
        }

        $email = (new TemplatedEmail())
            ->from(Address::create($this->fromAddress))
            ->to($to)
            ->subject(sprintf('Votre acquisition %s est confirmée', $order->getReference()))
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }
}
