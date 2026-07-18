<?php

namespace App\Controller;

use App\Payment\OrderPaymentFinalizer;
use App\Repository\OrderRepository;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Réception des événements Stripe. C'est la SOURCE DE VÉRITÉ du paiement : on ne se fie
 * jamais au seul retour navigateur (success_url). Signature vérifiée, traitement idempotent.
 */
class StripeWebhookController extends AbstractController
{
    public function __construct(
        private readonly string $stripeWebhookSecret,
        private readonly OrderRepository $orders,
        private readonly OrderPaymentFinalizer $finalizer,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/webhook/stripe', name: 'app_stripe_webhook', methods: ['POST'])]
    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature', '');

        if ($this->stripeWebhookSecret === '') {
            $this->logger->error('Webhook Stripe reçu mais STRIPE_WEBHOOK_SECRET non configuré.');

            return new Response('Webhook non configuré.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature, $this->stripeWebhookSecret);
        } catch (\UnexpectedValueException|SignatureVerificationException $e) {
            $this->logger->warning('Webhook Stripe invalide : {msg}', ['msg' => $e->getMessage()]);

            return new Response('Signature invalide.', Response::HTTP_BAD_REQUEST);
        }

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $reference = $session->client_reference_id ?? ($session->metadata['order_reference'] ?? null);
            $order = $reference ? $this->orders->findOneByReference($reference) : null;
            if ($order === null && \is_string($session->id)) {
                $order = $this->orders->findOneByStripeSessionId($session->id);
            }

            if ($order === null) {
                $this->logger->warning('Webhook : commande introuvable pour la session {id}.', ['id' => $session->id]);

                return new Response('Commande introuvable.', Response::HTTP_NOT_FOUND);
            }

            $paymentIntent = \is_string($session->payment_intent) ? $session->payment_intent : null;
            $this->finalizer->markPaid($order, $paymentIntent);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}
