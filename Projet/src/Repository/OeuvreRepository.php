<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Oeuvre>
 */
class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    //    /**
    //     * @return Oeuvre[] Returns an array of Oeuvre objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Oeuvre
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

// src/Repository/OeuvreRepository.php

public function findByFilters(?string $keyword, ?string $artiste, ?string $year, ?string $type, ?string $technique, ?string $lieuCreation, ?string $dimensions, ?string $mouvement, ?string $collection): array
{
    $qb = $this->createQueryBuilder('o');

    if ($keyword) {
        $qb->andWhere('o.titre LIKE :keyword OR o.description LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%');
    }

    if ($artiste) {
        $qb->andWhere('o.artiste LIKE :artiste')
            ->setParameter('artiste', '%' . $artiste . '%');
    }

    if ($year) {
        $qb->andWhere('YEAR(o.date) = :year')
            ->setParameter('year', $year);
    }

    if ($type) {
        $qb->andWhere('o.type = :type')
            ->setParameter('type', $type);
    }

    if ($technique) {
        $qb->andWhere('o.technique = :technique')
            ->setParameter('technique', $technique);
    }

    if ($lieuCreation) {
        $qb->andWhere('o.lieuCreation LIKE :lieuCreation')
            ->setParameter('lieuCreation', '%' . $lieuCreation . '%');
    }

    if ($dimensions) {
        $qb->andWhere('o.dimensions LIKE :dimensions')
            ->setParameter('dimensions', '%' . $dimensions . '%');
    }

    if ($mouvement) {
        $qb->andWhere('o.mouvement LIKE :mouvement')
            ->setParameter('mouvement', '%' . $mouvement . '%');
    }

    if ($collection) {
        $qb->andWhere('o.collection LIKE :collection')
            ->setParameter('collection', '%' . $collection . '%');
    }

    return $qb->getQuery()->getResult();
}


}
