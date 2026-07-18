<?php

namespace App\Tests\Tracking;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StatutPaiement;
use App\Tracking\TrackingAdvancer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Workflow\WorkflowInterface;

class TrackingAdvancerTest extends KernelTestCase
{
    public function testAdvancesThroughStatesButNeverDelivers(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $advancer = static::getContainer()->get(TrackingAdvancer::class);

        $order = $this->paidOrder($em);

        // 1er passage : préparation
        self::assertTrue($advancer->advance($order));
        self::assertSame('en_preparation', $order->getEtat());

        // 2e : expédition
        self::assertTrue($advancer->advance($order));
        self::assertSame('expediee', $order->getEtat());

        // 3e : mise en transit
        self::assertTrue($advancer->advance($order));
        self::assertSame('en_transit', $order->getEtat());

        // Ensuite : dérive infinie, jamais « livree »
        for ($i = 0; $i < 20; ++$i) {
            self::assertTrue($advancer->advance($order));
            self::assertSame('en_transit', $order->getEtat(), 'La commande ne doit jamais quitter « en_transit ».');
        }

        self::assertNotSame('livree', $order->getEtat());
        // 1 (confirmation) + 3 (jalons) + 20 (dérive) = 24 événements
        self::assertCount(24, $order->getTrackingEvents());
    }

    public function testWorkflowHasNoTransitionToLivree(): void
    {
        self::bootKernel();
        /** @var WorkflowInterface $wf */
        $wf = static::getContainer()->get('state_machine.order');

        $toLivree = array_filter(
            $wf->getDefinition()->getTransitions(),
            static fn ($t) => \in_array('livree', $t->getTos(), true),
        );
        self::assertSame([], $toLivree, 'Aucune transition ne doit mener à « livree ».');
    }

    private function paidOrder(EntityManagerInterface $em): Order
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
            ->setEtat('payee');
        $order->addItem(
            (new OrderItem())->setProductNom('Le Vide Contenu')->setProductSku('MB-4042-77')->setPrixUnitaireCents(240000)->setQuantite(1)
        );
        // Jalon initial, tel que posé par la finalisation du paiement.
        $order->addTrackingEvent(new \App\Entity\TrackingEvent($order, \App\Tracking\TrackingNarrative::CONFIRMATION));
        $em->persist($order);
        $em->flush();

        return $order;
    }
}
