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

    public function findByFilters(
        ?string $keyword, 
        ?string $artiste, 
        ?string $year, 
        ?string $type, 
        ?string $technique, 
        ?string $lieu_creation, 
        ?string $dimensions, 
        ?string $mouvement, 
        ?string $collection
    ): array {
        $qb = $this->createQueryBuilder('o');

        // Recherche globale avec le mot-clé (insensible à la casse et correspondance partielle)
        if ($keyword) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(o.titre) LIKE :keyword',
                    'LOWER(o.description) LIKE :keyword',
                    'LOWER(o.artiste) LIKE :keyword',
                    'LOWER(o.type) LIKE :keyword',
                    'LOWER(o.technique) LIKE :keyword',
                    'LOWER(o.lieu_creation) LIKE :keyword',
                    'LOWER(o.dimensions) LIKE :keyword',
                    'LOWER(o.mouvement) LIKE :keyword',
                    'LOWER(o.collection) LIKE :keyword'
                )
            )
            ->setParameter('keyword', '%' . strtolower($keyword) . '%');
        }

        // Filtre par artiste (insensible à la casse et correspondance partielle)
        if ($artiste) {
            $qb->andWhere('LOWER(o.artiste) LIKE :artiste')
                ->setParameter('artiste', '%' . strtolower($artiste) . '%');
        }

        // Filtre par année (extraction de l'année)
        if ($year) {
            $qb->andWhere('YEAR(o.date) = :year')
                ->setParameter('year', $year);
        }

        // Filtre par type exact (insensible à la casse)
        if ($type) {
            $qb->andWhere('LOWER(o.type) = :type')
                ->setParameter('type', strtolower($type));
        }

        // Filtre par technique (insensible à la casse et correspondance partielle)
        if ($technique) {
            $qb->andWhere('LOWER(o.technique) LIKE :technique')
                ->setParameter('technique', '%' . strtolower($technique) . '%');
        }

        // Filtre par lieu de création (insensible à la casse et correspondance partielle)
        if ($lieu_creation) {
            $qb->andWhere('LOWER(o.lieu_creation) LIKE :lieu_creation')
                ->setParameter('lieu_creation', '%' . strtolower($lieu_creation) . '%');
        }

        // Filtre par dimensions (insensible à la casse et correspondance partielle)
        if ($dimensions) {
            $qb->andWhere('LOWER(o.dimensions) LIKE :dimensions')
                ->setParameter('dimensions', '%' . strtolower($dimensions) . '%');
        }

        // Filtre par mouvement (insensible à la casse et correspondance partielle)
        if ($mouvement) {
            $qb->andWhere('LOWER(o.mouvement) LIKE :mouvement')
                ->setParameter('mouvement', '%' . strtolower($mouvement) . '%');
        }

        // Filtre par collection (insensible à la casse et correspondance partielle)
        if ($collection) {
            $qb->andWhere('LOWER(o.collection) LIKE :collection')
                ->setParameter('collection', '%' . strtolower($collection) . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
