<?php

namespace App\Tests\Tracking;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StatutPaiement;
use App\Entity\TrackingEvent;
use App\Tracking\TrackingNarrative;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SuiviTest extends WebTestCase
{
    public function testSuiviFormIsShownByDefault(): void
    {
        $client = static::createClient();
        $client->request('GET', '/suivi');
        $this->assertResponseIsSuccessful();
        self::assertSelectorExists('input[name="reference"]');
    }

    public function testWrongEmailIsRejected(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $order = $this->trackedOrder($em);

        $crawler = $client->request('GET', '/suivi');
        $form = $crawler->selectButton('Consulter le convoyage')->form();
        $form['reference'] = $order->getReference();
        $form['email'] = 'pirate@exemple.fr';
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Aucune commande ne correspond', (string) $client->getResponse()->getContent());
    }

    public function testCorrectReferenceAndEmailShowsTracking(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $order = $this->trackedOrder($em);

        $crawler = $client->request('GET', '/suivi');
        $form = $crawler->selectButton('Consulter le convoyage')->form();
        $form['reference'] = $order->getReference();
        $form['email'] = 'client@exemple.fr';
        $client->submit($form);

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('BON DE CONVOYAGE', $body);
        self::assertStringContainsString($order->getReference(), $body);
        self::assertStringContainsString('Historique du convoyage', $body);
        self::assertStringContainsString(TrackingNarrative::CONFIRMATION, $body);
    }

    private function trackedOrder(EntityManagerInterface $em): Order
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
            ->setStatutPaiement(StatutPaiement::Paye)
            ->setEtat('en_transit');
        $order->addItem(
            (new OrderItem())->setProductNom('Le Vide Contenu')->setProductSku('MB-4042-77')->setPrixUnitaireCents(240000)->setQuantite(1)
        );
        $order->addTrackingEvent(new TrackingEvent($order, TrackingNarrative::CONFIRMATION));
        $em->persist($order);
        $em->flush();

        return $order;
    }
}
