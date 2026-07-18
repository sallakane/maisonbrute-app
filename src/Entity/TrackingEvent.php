<?php

namespace App\Entity;

use App\Repository\TrackingEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un événement du « bon de convoyage ». Le fil du suivi : des libellés qui dérivent vers
 * l'existentiel, ajoutés périodiquement par le scheduler, et qui n'annoncent jamais l'arrivée.
 */
#[ORM\Entity(repositoryClass: TrackingEventRepository::class)]
class TrackingEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trackingEvents')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false)]
    private ?Order $orderRef = null;

    #[ORM\Column(length: 200)]
    private string $libelle = '';

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct(?Order $order = null, string $libelle = '')
    {
        $this->orderRef = $order;
        $this->libelle = $libelle;
        $this->createdAt = new \DateTimeImmutable();
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

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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
