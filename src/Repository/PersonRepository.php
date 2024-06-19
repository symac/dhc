<?php

namespace App\Repository;

use App\Controller\IndexController;
use App\Entity\Person;
use App\Entity\University;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Person>
 */
class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function findOneByQid(string $qid): ?Person
    {
        return $this->createQueryBuilder('p')
            ->select( 'p', 'a', 'd', 'a', 'u')
            ->leftJoin('p.awards', 'a')
            ->leftJoin('a.doctorate', 'd')
            ->leftJoin('d.university', 'u')
            ->andWhere('p.qid = :qid')
            ->setParameter('qid', $qid)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }

    public function findImagesNeedUpdate(): array {
        return $this->createQueryBuilder('p')
            ->where("p.image is not null and (p.imageCreator is null OR p.imageLicense is null)")
            ->getQuery()
            ->getResult();
    }

    public function updateCount(): void
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "UPDATE `person` set count_awards=(select count(*) from award where award.person_id = person.id);";
        $stmt = $conn->prepare($sql);
        $stmt->executeQuery();
    }

    public function getYearStats(): array {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT SUBSTR(display_date, 1, 3) as year, gender, count(*) as nb FROM person, award where person.id = award.person_id group by year, gender";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        $yearStats = [];
        $years = [];

        foreach ($result->fetchAllAssociative() as $row) {
            if ($row['year'] == "") {
                $row["year"] = "année inconnue";
            } else {
                $row["year"] = $row['year']."0";
            }

            if ($row['gender'] == "") {
                $row['genderLabel'] = IndexController::genderMapLabel(null);
                $row["genderColour"] = IndexController::genderMapColour(null);
            } else {
                $row["genderLabel"] = IndexController::genderMapLabel($row['gender']);
                $row["genderColour"] = IndexController::genderMapColour($row['gender']);
            }

            $yearStats[$row['genderLabel']]["stats"][$row['year']] = $row['nb'];
            $yearStats[$row['genderLabel']]["colour"] = $row['genderColour'];
            $years[$row['year']] = $row['year'];
        }

        foreach ($yearStats as $key => $value) {
            foreach ($years as $year) {
                if (!isset($yearStats[$key]["stats"][$year])) {
                    $yearStats[$key]["stats"][$year] = 0;
                }

                ksort($yearStats[$key]["stats"]);
            }
        }

        return $yearStats;
    }

    public function getGenderStats(): array {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT gender, count(*) as nb FROM person group by gender;";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
    //    /**
    //     * @return Person[] Returns an array of Person objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Person
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
