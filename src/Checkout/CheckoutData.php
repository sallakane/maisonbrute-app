<?php

namespace App\Checkout;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Données saisies au tunnel de commande (invité ou client). Validées avant création de l'Order.
 */
class CheckoutData
{
    #[Assert\NotBlank(message: 'Une adresse e-mail est requise pour vous tenir informé de l\'attente.')]
    #[Assert\Email(message: 'Cette adresse e-mail ne semble pas valide.')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le nom du destinataire est requis.')]
    #[Assert\Length(max: 120)]
    public ?string $nom = null;

    #[Assert\NotBlank(message: 'Une adresse de livraison est requise (même si elle ne servira pas).')]
    #[Assert\Length(max: 200)]
    public ?string $ligne1 = null;

    #[Assert\NotBlank(message: 'Le code postal est requis.')]
    #[Assert\Length(max: 20)]
    public ?string $cp = null;

    #[Assert\NotBlank(message: 'La ville est requise.')]
    #[Assert\Length(max: 120)]
    public ?string $ville = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 80)]
    public string $pays = 'France';

    #[Assert\NotBlank(message: 'Choisissez un mode de convoyage.')]
    #[Assert\Choice(callback: [Transporteur::class, 'codes'], message: 'Transporteur inconnu.')]
    public ?string $transporteur = null;
}
