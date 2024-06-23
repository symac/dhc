<?php

namespace App\Repository;

use App\Entity\University;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<University>
 */
class UniversityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, University::class);
    }

    public function findOneByQid(string $qid): ?University
    {
        return $this->createQueryBuilder('u')
            ->select('u', 'd', 'a', 'p')
            ->leftJoin('u.doctorates', 'd')
            ->leftJoin('d.awards', 'a')
            ->leftJoin('a.person', 'p')
            ->andWhere('u.qid = :qid')
            ->setParameter('qid', $qid)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllForIndex(): array  {
        return $this->createQueryBuilder('u')
            ->select('u', 'd', 'a', 'p')
            ->leftJoin('u.doctorates', 'd')
            ->leftJoin('d.awards', 'a')
            ->leftJoin('a.person', 'p')
            ->orderBy('UPPER(u.label)', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getGenderStats(University $university): array {
        foreach ($university->getDoctorates() as $doctorate) {
            $conn = $this->getEntityManager()->getConnection();
            $sql = "SELECT gender, count(*) as nb FROM person, award where award.doctorate_id = ? and award.person_id = person.id group by gender;";
            $stmt = $conn->prepare($sql);
            $result = $stmt->executeQuery([$doctorate->getId()]);
            $resultArray = $result->fetchAllAssociative();
        }

        return $resultArray;
    }

//    /**
//     * @return University[] Returns an array of University objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?University
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
