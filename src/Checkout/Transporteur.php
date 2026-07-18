<?php

namespace App\Checkout;

/**
 * Modes de convoyage proposés. Tous « gratuits » : le port n'est jamais réellement engagé.
 * Le délai est, par principe, indéterminé.
 *
 * @phpstan-type TransporteurDef array{code: string, nom: string, promesse: string, fraisCents: int}
 */
final class Transporteur
{
    /** @var array<string, array{nom: string, promesse: string, fraisCents: int}> */
    public const CHOIX = [
        'standard' => [
            'nom' => 'Convoyage Standard',
            'promesse' => 'Départ imminent, arrivée conjecturale. Sans supplément.',
            'fraisCents' => 0,
        ],
        'express' => [
            'nom' => 'Convoyage Express',
            'promesse' => 'La même attente, mais avec un sentiment d\'urgence. Sans supplément.',
            'fraisCents' => 0,
        ],
    ];

    /** @return list<string> */
    public static function codes(): array
    {
        return array_keys(self::CHOIX);
    }

    public static function nom(string $code): string
    {
        return self::CHOIX[$code]['nom'] ?? $code;
    }

    public static function fraisCents(string $code): int
    {
        return self::CHOIX[$code]['fraisCents'] ?? 0;
    }
}
