<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Menu>
 */
class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    public function createQueryBuilderForFilters(?int $themeId, ?int $regimeId, ?int $nbPersonnes, ?float $prixMax)
    {
        $qb = $this->createQueryBuilder('m');

        if ($themeId) {
            $qb->innerJoin('m.theme', 't')
               ->andWhere('t.id = :themeId')
               ->setParameter('themeId', $themeId);
        }

        if ($regimeId) {
            $qb->innerJoin('m.regimes', 'r')
               ->andWhere('r.id = :regimeId')
               ->setParameter('regimeId', $regimeId);
        }

        if ($nbPersonnes) {
            $qb->andWhere('m.nombrePersonneMin <= :nbPersonnes')
               ->setParameter('nbPersonnes', $nbPersonnes);
        }

        if ($prixMax) {
            $qb->andWhere('m.prixParPersonne <= :prixMax')
               ->setParameter('prixMax', $prixMax);
        }

        return $qb->distinct();
    }

    //    /**
    //     * @return Menu[] Returns an array of Menu objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Menu
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
