<?php

namespace App\Repository;

use App\Entity\JournalArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JournalArticle>
 */
class JournalArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JournalArticle::class);
    }

    /**
     * Articles publiés (date renseignée et passée), du plus récent au plus ancien.
     *
     * @return list<JournalArticle>
     */
    public function findPublies(?int $limit = null): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.publieLe IS NOT NULL')
            ->andWhere('a.publieLe <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.publieLe', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOnePublieBySlug(string $slug): ?JournalArticle
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.slug = :slug')
            ->andWhere('a.publieLe IS NOT NULL')
            ->andWhere('a.publieLe <= :now')
            ->setParameter('slug', $slug)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
