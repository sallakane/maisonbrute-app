<?php

namespace App\Entity;

enum StatutPaiement: string
{
    case EnAttente = 'en_attente';
    case Paye = 'paye';
    case Echoue = 'echoue';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de paiement',
            self::Paye => 'Payé',
            self::Echoue => 'Échoué',
        };
    }
}
