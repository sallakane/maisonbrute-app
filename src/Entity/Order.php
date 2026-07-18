<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une commande. Peut être passée en invité (customer null + emailInvite) ou par un client connecté.
 * L'adresse de livraison est figée sur la commande (snapshot). L'état (`etat`) est piloté par le
 * Symfony Workflow `order` — et n'atteint jamais « livree », c'est le principe de la Maison.
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true)]
    private string $reference = '';

    #[ORM\ManyToOne]
    private ?User $customer = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $emailInvite = null;

    // --- Adresse de livraison (snapshot) ---
    #[ORM\Column(length: 120)]
    private string $livraisonNom = '';

    #[ORM\Column(length: 200)]
    private string $livraisonLigne1 = '';

    #[ORM\Column(length: 20)]
    private string $livraisonCp = '';

    #[ORM\Column(length: 120)]
    private string $livraisonVille = '';

    #[ORM\Column(length: 80)]
    private string $livraisonPays = 'France';

    #[ORM\Column(length: 80)]
    private string $transporteur = '';

    #[ORM\Column]
    private int $fraisPortCents = 0;

    #[ORM\Column]
    private int $montantCents = 0;

    #[ORM\Column(length: 3)]
    private string $devise = 'EUR';

    #[ORM\Column(length: 20, enumType: StatutPaiement::class)]
    private StatutPaiement $statutPaiement = StatutPaiement::EnAttente;

    /** Place courante du workflow `order`. Démarre à « panier ». */
    #[ORM\Column(length: 30)]
    private string $etat = 'panier';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->reference !== '' ? $this->reference : 'Commande';
    }

    public function getEmail(): ?string
    {
        return $this->customer?->getEmail() ?? $this->emailInvite;
    }

    public function isPaye(): bool
    {
        return $this->statutPaiement === StatutPaiement::Paye;
    }

    public function getStatutPaiementLabel(): string
    {
        return $this->statutPaiement->label();
    }

    public function getMontantEuros(): float
    {
        return $this->montantCents / 100;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getEmailInvite(): ?string
    {
        return $this->emailInvite;
    }

    public function setEmailInvite(?string $emailInvite): static
    {
        $this->emailInvite = $emailInvite;

        return $this;
    }

    public function getLivraisonNom(): string
    {
        return $this->livraisonNom;
    }

    public function setLivraisonNom(string $livraisonNom): static
    {
        $this->livraisonNom = $livraisonNom;

        return $this;
    }

    public function getLivraisonLigne1(): string
    {
        return $this->livraisonLigne1;
    }

    public function setLivraisonLigne1(string $livraisonLigne1): static
    {
        $this->livraisonLigne1 = $livraisonLigne1;

        return $this;
    }

    public function getLivraisonCp(): string
    {
        return $this->livraisonCp;
    }

    public function setLivraisonCp(string $livraisonCp): static
    {
        $this->livraisonCp = $livraisonCp;

        return $this;
    }

    public function getLivraisonVille(): string
    {
        return $this->livraisonVille;
    }

    public function setLivraisonVille(string $livraisonVille): static
    {
        $this->livraisonVille = $livraisonVille;

        return $this;
    }

    public function getLivraisonPays(): string
    {
        return $this->livraisonPays;
    }

    public function setLivraisonPays(string $livraisonPays): static
    {
        $this->livraisonPays = $livraisonPays;

        return $this;
    }

    public function getTransporteur(): string
    {
        return $this->transporteur;
    }

    public function setTransporteur(string $transporteur): static
    {
        $this->transporteur = $transporteur;

        return $this;
    }

    public function getFraisPortCents(): int
    {
        return $this->fraisPortCents;
    }

    public function setFraisPortCents(int $fraisPortCents): static
    {
        $this->fraisPortCents = $fraisPortCents;

        return $this;
    }

    public function getMontantCents(): int
    {
        return $this->montantCents;
    }

    public function setMontantCents(int $montantCents): static
    {
        $this->montantCents = $montantCents;

        return $this;
    }

    public function getDevise(): string
    {
        return $this->devise;
    }

    public function setDevise(string $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getStatutPaiement(): StatutPaiement
    {
        return $this->statutPaiement;
    }

    public function setStatutPaiement(StatutPaiement $statutPaiement): static
    {
        $this->statutPaiement = $statutPaiement;

        return $this;
    }

    public function getEtat(): string
    {
        return $this->etat;
    }

    public function setEtat(string $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrderRef($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrderRef() === $this) {
                $item->setOrderRef(null);
            }
        }

        return $this;
    }
}
