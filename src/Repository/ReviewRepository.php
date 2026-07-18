<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return list<Review>
     */
    public function findModeres(?int $limit = null): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.modere = true')
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Agrégat global des avis modérés.
     *
     * @return array{count: int, moyenne: float}
     */
    public function aggregatGlobal(): array
    {
        $row = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS nb', 'AVG(r.note) AS moyenne')
            ->andWhere('r.modere = true')
            ->getQuery()
            ->getSingleResult();

        return [
            'count' => (int) $row['nb'],
            'moyenne' => $row['moyenne'] !== null ? round((float) $row['moyenne'], 1) : 0.0,
        ];
    }

    /**
     * Agrégat des avis modérés d'un produit.
     *
     * @return array{count: int, moyenne: float}
     */
    public function aggregatProduit(Product $product): array
    {
        $row = $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS nb', 'AVG(r.note) AS moyenne')
            ->andWhere('r.modere = true')
            ->andWhere('r.product = :p')
            ->setParameter('p', $product)
            ->getQuery()
            ->getSingleResult();

        return [
            'count' => (int) $row['nb'],
            'moyenne' => $row['moyenne'] !== null ? round((float) $row['moyenne'], 1) : 0.0,
        ];
    }

    /**
     * @return list<Review>
     */
    public function findModeresParProduit(Product $product): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.modere = true')
            ->andWhere('r.product = :p')
            ->setParameter('p', $product)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
