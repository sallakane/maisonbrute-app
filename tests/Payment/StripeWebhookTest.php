<?php

namespace App\Tests\Payment;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StatutPaiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StripeWebhookTest extends WebTestCase
{
    private const WEBHOOK_SECRET = 'whsec_test_secret';

    public function testInvalidSignatureIsRejected(): void
    {
        $client = static::createClient();
        $client->request('POST', '/webhook/stripe', server: ['HTTP_STRIPE_SIGNATURE' => 't=1,v1=deadbeef'], content: '{}');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testCheckoutCompletedMarksOrderPaidAndSendsEmail(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $order = $this->createPendingOrder($em);
        $reference = $order->getReference();

        $payload = json_encode([
            'id' => 'evt_test_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'object' => 'checkout.session',
                'client_reference_id' => $reference,
                'payment_intent' => 'pi_test_123',
                'metadata' => ['order_reference' => $reference],
            ]],
        ], \JSON_THROW_ON_ERROR);

        $client->request('POST', '/webhook/stripe',
            server: ['HTTP_STRIPE_SIGNATURE' => $this->sign($payload)],
            content: $payload,
        );

        $this->assertResponseIsSuccessful();
        self::assertEmailCount(1);

        $em->clear();
        $refreshed = $em->getRepository(Order::class)->findOneBy(['reference' => $reference]);
        self::assertNotNull($refreshed);
        self::assertSame(StatutPaiement::Paye, $refreshed->getStatutPaiement());
        self::assertSame('payee', $refreshed->getEtat());
        self::assertSame('pi_test_123', $refreshed->getStripePaymentIntentId());
    }

    public function testWebhookIsIdempotent(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $order = $this->createPendingOrder($em);
        $reference = $order->getReference();
        $payload = json_encode([
            'id' => 'evt_test_2', 'object' => 'event', 'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_x', 'client_reference_id' => $reference, 'payment_intent' => 'pi_x']],
        ], \JSON_THROW_ON_ERROR);

        $client->request('POST', '/webhook/stripe', server: ['HTTP_STRIPE_SIGNATURE' => $this->sign($payload)], content: $payload);
        self::assertEmailCount(1); // premier passage : e-mail envoyé

        // Rejeu du même événement : commande déjà payée → aucun nouvel e-mail.
        $client->request('POST', '/webhook/stripe', server: ['HTTP_STRIPE_SIGNATURE' => $this->sign($payload)], content: $payload);
        $this->assertResponseIsSuccessful();
        self::assertEmailCount(0);
    }

    private function createPendingOrder(EntityManagerInterface $em): Order
    {
        $order = (new Order())
            ->setReference('MB-'.strtoupper(bin2hex(random_bytes(3))))
            ->setEmailInvite('client@exemple.fr')
            ->setLivraisonNom('Camille Durand')
            ->setLivraisonLigne1('14 rue de la Patience')
            ->setLivraisonCp('75011')
            ->setLivraisonVille('Paris')
            ->setTransporteur('Convoyage Standard')
            ->setMontantCents(240000)
            ->setStatutPaiement(StatutPaiement::EnAttente);
        $item = (new OrderItem())
            ->setProductNom('Le Vide Contenu')
            ->setProductSku('MB-4042-77')
            ->setPrixUnitaireCents(240000)
            ->setQuantite(1);
        $order->addItem($item);

        $em->persist($order);
        $em->flush();

        return $order;
    }

    private function sign(string $payload): string
    {
        $t = time();
        $signature = hash_hmac('sha256', $t.'.'.$payload, self::WEBHOOK_SECRET);

        return sprintf('t=%d,v1=%s', $t, $signature);
    }
}
