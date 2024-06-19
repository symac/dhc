<?php

namespace App\Repository;

use App\Entity\Country;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Country>
 */
class CountryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Country::class);
    }

    public function findAll(): array {
        return $this->createQueryBuilder('c')
            ->select('c', 'p')
            ->leftJoin('c.persons', 'p')
            ->orderBy('c.label', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findCountryWithDetail(string $qid): Country {
        return $this->createQueryBuilder('c')
            ->select('c', 'p', 'a', 'd', 'u')
            ->leftJoin('c.persons', 'p')
            ->leftJoin('p.awards', 'a')
            ->leftJoin('a.doctorate', 'd')
            ->leftJoin('d.university', 'u')
            ->where('c.qid LIKE :qid')
            ->setParameter('qid', $qid)
            ->orderBy('p.label', 'ASC')
            ->addOrderBy('a.displayDate', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return Country[] Returns an array of Country objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Country
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
