<?php

namespace App\Twig;

use App\Entity\StatutPaiement;
use App\Repository\OrderRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Fonctions Twig transverses au site (footer, compteur planétaire…).
 */
class SiteExtension extends AbstractExtension
{
    /** Base absurde de départ du compteur planétaire. */
    private const BASE = 4_042_891_337;

    public function __construct(private readonly OrderRepository $orders)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('planet_counter_start', $this->planetCounterStart(...)),
        ];
    }

    /**
     * Point de départ du compteur « commandes que la Terre ne pourra jamais honorer » :
     * une base absurde, ancrée sur le nombre réel de commandes payées de la boutique.
     */
    public function planetCounterStart(): int
    {
        $reelles = $this->orders->count(['statutPaiement' => StatutPaiement::Paye]);

        return self::BASE + $reelles * 111;
    }
}
