<?php

namespace App\Tracking;

/**
 * Les libellés du convoyage. Les premiers jalons sont fixes (paiement → transit), puis le
 * colis entre dans une dérive existentielle : une liste de statuts qui n'annoncent jamais
 * l'arrivée. C'est le cœur de la blague — inscrit dans les données, pas seulement à l'écran.
 */
final class TrackingNarrative
{
    public const CONFIRMATION = 'Commande confirmée. Paiement reçu.';
    public const PREPARATION = 'Votre commande a été confiée à nos ateliers.';
    public const EXPEDITION = 'Votre colis a quitté notre entrepôt.';
    public const MISE_EN_TRANSIT = 'Votre colis transite par un premier centre de tri.';

    /** Puits sans fond de statuts pour un colis « en transit » — piochés au hasard, à vie. */
    public const DERIVE = [
        'Votre colis transite par un centre de tri.',
        'Léger retard : acheminement soumis aux limites physiques de la planète.',
        'Votre colis prend le temps de la réflexion.',
        'Votre colis existe. C’est déjà beaucoup.',
        'Nous cherchons encore le sens de ce trajet.',
        'Votre colis a été aperçu près d’un centre de tri, puis plus rien.',
        'Position estimée : quelque part entre deux centres de tri.',
        'Le convoyeur médite sur la notion même de destination.',
        'Votre colis observe un moment de recueillement.',
        'Acheminement suspendu à une question sans réponse.',
        'Votre colis progresse, dans un sens qui nous échappe.',
        'Recalcul de l’itinéraire vers l’inatteignable.',
    ];

    /**
     * Choisit un libellé de dérive au hasard, en évitant si possible de répéter le précédent.
     */
    public static function prochaineDerive(?string $precedent = null): string
    {
        $choix = self::DERIVE;
        if ($precedent !== null) {
            $filtres = array_values(array_filter($choix, static fn (string $l) => $l !== $precedent));
            if ($filtres !== []) {
                $choix = $filtres;
            }
        }

        return $choix[array_rand($choix)];
    }
}
