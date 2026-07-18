<?php

namespace App\Payment;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Crée une session Stripe Checkout (page de paiement hébergée) pour une commande.
 *
 * ⚖️ Garde-fou juridique : refuse toute clé secrète qui n'est pas une clé de TEST
 * (`sk_test_…`). On n'encaisse jamais réellement — c'est une satire, pas un commerce.
 */
class StripeCheckoutService
{
    public function __construct(
        private readonly string $secretKey,
        private readonly EntityManagerInterface $em,
        private readonly UrlGeneratorInterface $urls,
    ) {
    }

    public function isConfigured(): bool
    {
        return str_starts_with($this->secretKey, 'sk_test_');
    }

    public function createSession(Order $order): Session
    {
        $this->assertTestKey();

        $lineItems = [];
        foreach ($order->getItems() as $item) {
            $lineItems[] = [
                'quantity' => $item->getQuantite(),
                'price_data' => [
                    'currency' => strtolower($order->getDevise()),
                    'unit_amount' => $item->getPrixUnitaireCents(),
                    'product_data' => [
                        'name' => $item->getProductNom(),
                        'description' => 'Réf. '.$item->getProductSku().' — livraison conjecturale',
                    ],
                ],
            ];
        }

        $client = new StripeClient($this->secretKey);
        $session = $client->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $order->getEmail(),
            'client_reference_id' => $order->getReference(),
            'metadata' => ['order_reference' => $order->getReference()],
            'success_url' => $this->urls->generate('app_checkout_confirmation', ['reference' => $order->getReference()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->urls->generate('app_cart', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $order->setStripeSessionId($session->id);
        if (\is_string($session->payment_intent)) {
            $order->setStripePaymentIntentId($session->payment_intent);
        }
        $this->em->flush();

        return $session;
    }

    private function assertTestKey(): void
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Stripe n\'est pas configuré en mode test (clé sk_test_… attendue). Aucun paiement ne peut être initié.');
        }
    }
}
