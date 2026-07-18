<?php

namespace App\Tests\Tracking;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StatutPaiement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AdvanceTrackingCommandTest extends KernelTestCase
{
    public function testCommandAdvancesPaidOrders(): void
    {
        $kernel = self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);

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
        $em->persist($order);
        $em->flush();

        $application = new Application($kernel);
        $tester = new CommandTester($application->find('app:orders:advance-tracking'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('nouveau jalon', $tester->getDisplay());

        $em->refresh($order);
        self::assertSame('en_preparation', $order->getEtat());
        self::assertNotSame('livree', $order->getEtat());
    }
}
