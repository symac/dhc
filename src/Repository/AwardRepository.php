<?php

namespace App\Repository;

use App\Entity\Award;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Award>
 */
class AwardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Award::class);
    }

    public function findAllWidthDoctorates(): array
    {
        return $this->createQueryBuilder('a')
            ->select('a', 'd', 'p', 'u')
            ->leftJoin('a.doctorate', 'd')
            ->leftJoin('a.person', 'p')
            ->leftJoin('d.universities', 'u')
            ->getQuery()
            ->getResult();
    }

    public function getRecent(int $count): array {
        return $this->createQueryBuilder('a')
            ->select('a', 'd', 'p', 'u')
            ->leftJoin('a.doctorate', 'd')
            ->leftJoin('a.person', 'p')
            ->leftJoin('d.universities', 'u')
            ->orderBy('a.creationDate', 'DESC')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return Award[] Returns an array of Award objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Award
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
