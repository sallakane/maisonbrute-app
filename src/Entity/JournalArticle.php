<?php

namespace App\Entity;

use App\Repository\JournalArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un article du Journal — le CMS éditorial. Principal moteur de trafic organique : contenu long,
 * satirique, optimisé SEO. Publié quand `publieLe` est renseigné et passé.
 */
#[ORM\Entity(repositoryClass: JournalArticleRepository::class)]
class JournalArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $titre = '';

    #[ORM\Column(length: 200, unique: true)]
    private string $slug = '';

    #[ORM\Column(length: 60)]
    private string $rubrique = 'Philosophie';

    /** Temps de lecture estimé, en minutes. */
    #[ORM\Column]
    private int $tempsLecture = 5;

    /** Chapô / accroche (résumé affiché en liste et en tête d'article). */
    #[ORM\Column(type: Types::TEXT)]
    private string $chapo = '';

    /** Corps de l'article, en HTML. */
    #[ORM\Column(type: Types::TEXT)]
    private string $corps = '';

    #[ORM\Column(length: 120)]
    private string $auteur = 'La Rédaction';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(length: 320, nullable: true)]
    private ?string $seoDescription = null;

    /** Date de publication. Null = brouillon ; futur = programmé. */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publieLe = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->titre !== '' ? $this->titre : 'Article';
    }

    public function isPublie(): bool
    {
        return $this->publieLe !== null && $this->publieLe <= new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getRubrique(): string
    {
        return $this->rubrique;
    }

    public function setRubrique(string $rubrique): static
    {
        $this->rubrique = $rubrique;

        return $this;
    }

    public function getTempsLecture(): int
    {
        return $this->tempsLecture;
    }

    public function setTempsLecture(int $tempsLecture): static
    {
        $this->tempsLecture = $tempsLecture;

        return $this;
    }

    public function getChapo(): string
    {
        return $this->chapo;
    }

    public function setChapo(string $chapo): static
    {
        $this->chapo = $chapo;

        return $this;
    }

    public function getCorps(): string
    {
        return $this->corps;
    }

    public function setCorps(string $corps): static
    {
        $this->corps = $corps;

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

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): static
    {
        $this->seoTitle = $seoTitle;

        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): static
    {
        $this->seoDescription = $seoDescription;

        return $this;
    }

    public function getPublieLe(): ?\DateTimeImmutable
    {
        return $this->publieLe;
    }

    public function setPublieLe(?\DateTimeImmutable $publieLe): static
    {
        $this->publieLe = $publieLe;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
