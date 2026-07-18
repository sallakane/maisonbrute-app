<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ligne de commande. Le nom et le prix sont figés (snapshot) au moment de l'achat,
 * car le catalogue peut évoluer. Le lien vers le produit est indicatif (nullable).
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false)]
    private ?Order $orderRef = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    #[ORM\Column(length: 160)]
    private string $productNom = '';

    #[ORM\Column(length: 60)]
    private string $productSku = '';

    #[ORM\Column]
    private int $prixUnitaireCents = 0;

    #[ORM\Column]
    private int $quantite = 1;

    public function getLigneTotalCents(): int
    {
        return $this->prixUnitaireCents * $this->quantite;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderRef(): ?Order
    {
        return $this->orderRef;
    }

    public function setOrderRef(?Order $orderRef): static
    {
        $this->orderRef = $orderRef;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getProductNom(): string
    {
        return $this->productNom;
    }

    public function setProductNom(string $productNom): static
    {
        $this->productNom = $productNom;

        return $this;
    }

    public function getProductSku(): string
    {
        return $this->productSku;
    }

    public function setProductSku(string $productSku): static
    {
        $this->productSku = $productSku;

        return $this;
    }

    public function getPrixUnitaireCents(): int
    {
        return $this->prixUnitaireCents;
    }

    public function setPrixUnitaireCents(int $prixUnitaireCents): static
    {
        $this->prixUnitaireCents = $prixUnitaireCents;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }
}
