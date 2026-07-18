<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Un avis client. Publié seulement après modération (`modere`). Peut viser un produit précis
 * ou la Maison en général (`product` null). Les clients patientent, et ils recommandent.
 */
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Product $product = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Indiquez au moins un nom ou un pseudonyme.')]
    #[Assert\Length(max: 120)]
    private string $auteur = '';

    /** Durée d'attente déclarée, affichée en méta (ex. « 3 ans »). Purement narratif. */
    #[ORM\Column(length: 80, nullable: true)]
    #[Assert\Length(max: 80)]
    private ?string $attente = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'La note doit être comprise entre 1 et 5 étoiles.')]
    private int $note = 5;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Un témoignage, même bref, est requis.')]
    #[Assert\Length(min: 3, max: 2000)]
    private string $texte = '';

    #[ORM\Column]
    private bool $modere = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s (%d★)', $this->auteur !== '' ? $this->auteur : 'Avis', $this->note);
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuteur(): string
    {
        return $this->auteur;
    }

    public function setAuteur(string $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getAttente(): ?string
    {
        return $this->attente;
    }

    public function setAttente(?string $attente): static
    {
        $this->attente = $attente;

        return $this;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getTexte(): string
    {
        return $this->texte;
    }

    public function setTexte(string $texte): static
    {
        $this->texte = $texte;

        return $this;
    }

    public function isModere(): bool
    {
        return $this->modere;
    }

    public function setModere(bool $modere): static
    {
        $this->modere = $modere;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
