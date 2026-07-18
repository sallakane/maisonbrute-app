<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un objet du catalogue. Deux descriptions : l'une vendeuse (marketing),
 * l'autre qui révèle la satire (vraie). Le prix est stocké en centimes.
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $nom = '';

    #[ORM\Column(length: 180, unique: true)]
    private string $slug = '';

    #[ORM\ManyToOne(inversedBy: 'products')]
    private ?Maison $maison = null;

    /** @var Collection<int, Category> */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'products')]
    private Collection $categories;

    /** Prix en centimes (ex. 240000 = 2 400,00 €). */
    #[ORM\Column]
    private int $prixCents = 0;

    #[ORM\Column(length: 3)]
    private string $devise = 'EUR';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionMarketing = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionVraie = null;

    #[ORM\Column(length: 60, unique: true)]
    private string $sku = '';

    /** Stock « affiché » — narratif, pas un compteur réel (ex. « 3 exemplaires. Sur Terre. »). */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $stockAffiche = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $badge = null;

    /** @var Collection<int, ProductImage> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $seoTitle = null;

    #[ORM\Column(length: 320, nullable: true)]
    private ?string $seoDescription = null;

    #[ORM\Column]
    private bool $publie = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: Review::class)]
    private Collection $reviews;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->nom !== '' ? $this->nom : 'Produit';
    }

    /** Prix en unités de devise (float) pour l'affichage. */
    public function getPrixEuros(): float
    {
        return $this->prixCents / 100;
    }

    /** Objet « épuisé » (narratif) — dérivé du stock affiché. Reste en catalogue, invendable. */
    public function isEpuise(): bool
    {
        return $this->stockAffiche !== null
            && str_contains(mb_strtolower($this->stockAffiche), 'épuisé');
    }

    /** Première image (ordonnée par position) ou null. */
    public function getImagePrincipale(): ?ProductImage
    {
        return $this->images->first() ?: null;
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

    public function getMaison(): ?Maison
    {
        return $this->maison;
    }

    public function setMaison(?Maison $maison): static
    {
        $this->maison = $maison;

        return $this;
    }

    /** @return Collection<int, Category> */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getPrixCents(): int
    {
        return $this->prixCents;
    }

    public function setPrixCents(int $prixCents): static
    {
        $this->prixCents = $prixCents;

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

    public function getDescriptionMarketing(): ?string
    {
        return $this->descriptionMarketing;
    }

    public function setDescriptionMarketing(?string $descriptionMarketing): static
    {
        $this->descriptionMarketing = $descriptionMarketing;

        return $this;
    }

    public function getDescriptionVraie(): ?string
    {
        return $this->descriptionVraie;
    }

    public function setDescriptionVraie(?string $descriptionVraie): static
    {
        $this->descriptionVraie = $descriptionVraie;

        return $this;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getStockAffiche(): ?string
    {
        return $this->stockAffiche;
    }

    public function setStockAffiche(?string $stockAffiche): static
    {
        $this->stockAffiche = $stockAffiche;

        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function setBadge(?string $badge): static
    {
        $this->badge = $badge;

        return $this;
    }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

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

    public function isPublie(): bool
    {
        return $this->publie;
    }

    public function setPublie(bool $publie): static
    {
        $this->publie = $publie;

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

    /** @return Collection<int, Review> */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }
}
