<?php

namespace App\Entity;

use App\Repository\MaisonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une « maison » (la marque / le fabricant fictif d'un objet).
 */
#[ORM\Entity(repositoryClass: MaisonRepository::class)]
class Maison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $nom = '';

    #[ORM\Column(length: 140, unique: true)]
    private string $slug = '';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<int, Product> */
    #[ORM\OneToMany(mappedBy: 'maison', targetEntity: Product::class)]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->nom !== '' ? $this->nom : 'Maison';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /** @return Collection<int, Product> */
    public function getProducts(): Collection
    {
        return $this->products;
    }
}
