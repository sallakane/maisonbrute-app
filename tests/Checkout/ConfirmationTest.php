<?php

namespace App\Tests\Checkout;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StatutPaiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ConfirmationTest extends WebTestCase
{
    public function testUnknownOrderIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/commande/confirmation/MB-INCONNU');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPaidOrderShowsConfirmed(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $order = $this->persistOrder($em, StatutPaiement::Paye, 'payee');

        $client->request('GET', '/commande/confirmation/'.$order->getReference());

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Paiement confirmé', $body);
        self::assertStringContainsString($order->getReference(), $body);
    }

    public function testPendingOrderWithoutSessionShowsPending(): void
    {
        // Pas de stripeSessionId → aucune réconciliation réseau n'est tentée.
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $order = $this->persistOrder($em, StatutPaiement::EnAttente, 'panier');

        $client->request('GET', '/commande/confirmation/'.$order->getReference());

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('en cours de confirmation', (string) $client->getResponse()->getContent());
    }

    private function persistOrder(EntityManagerInterface $em, StatutPaiement $statut, string $etat): Order
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
            ->setStatutPaiement($statut)
            ->setEtat($etat);
        $order->addItem(
            (new OrderItem())->setProductNom('Le Vide Contenu')->setProductSku('MB-4042-77')->setPrixUnitaireCents(240000)->setQuantite(1)
        );
        $em->persist($order);
        $em->flush();

        return $order;
    }
}
