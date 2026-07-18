<?php

namespace App\Command;

use App\Entity\StatutPaiement;
use App\Repository\OrderRepository;
use App\Tracking\TrackingAdvancer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Fait avancer le suivi de toutes les commandes payées : prépare, expédie, met en transit,
 * puis ajoute un statut de dérive aux colis déjà en transit. N'aboutit jamais à « livree ».
 * À planifier (cron / Scheduler), typiquement une fois par jour.
 */
#[AsCommand(
    name: 'app:orders:advance-tracking',
    description: 'Ajoute un jalon de convoyage à chaque commande payée (le colis n\'arrive jamais).',
)]
class AdvanceTrackingCommand extends Command
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly TrackingAdvancer $advancer,
    ) {
        parent::__construct();
    }

    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $commandes = $this->orders->findBy(['statutPaiement' => StatutPaiement::Paye]);
        $avances = 0;
        foreach ($commandes as $order) {
            if ($this->advancer->advance($order)) {
                ++$avances;
                $io->writeln(sprintf('  <info>%s</info> → %s (%s)', $order->getReference(), $order->getDernierEvent()?->getLibelle(), $order->getEtat()));
            }
        }

        $io->success(sprintf('%d commande(s) sur %d ont reçu un nouveau jalon. Aucune n\'est arrivée.', $avances, \count($commandes)));

        return Command::SUCCESS;
    }
}
