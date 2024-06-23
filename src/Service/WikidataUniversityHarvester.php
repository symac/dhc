<?php

namespace App\Service;

use App\Entity\Award;
use App\Entity\Doctorate;
use App\Entity\Person;
use App\Entity\University;
use Doctrine\ORM\EntityManagerInterface;
use EasyRdf\Sparql\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class WikidataUniversityHarvester
{

    private EntityManagerInterface $em;
    private HttpClientInterface $httpClient;


    public function __construct(EntityManagerInterface $entityManager, httpclientInterface $httpClient)
    {
        $this->em = $entityManager;
        $this->httpClient = $httpClient;
    }

    public function run(): int
    {
        $countCreate = 0;
        $sparql = new Client("https://query.wikidata.org/sparql");

        # Récupération des universités et des doctorats honoris causa associés
        $universities = $this->em->getRepository(University::class)->findAll();
        $doctorates = $this->em->getRepository(Doctorate::class)->findAll();

        $existingUniversities = [];
        foreach ($universities as $university) {
            $existingUniversities[$university->getQid()] = $university;
        }



        $existingDoctorates = [];
        foreach ($doctorates as $doctorate) {
            $existingDoctorates[$doctorate->getQid()] = $doctorate;
        }

        // Copy array existingUniversities
        $remainingUniversities = $existingUniversities;
        $remainingDoctorates = $existingDoctorates;

        try {
            $result = $sparql->query('SELECT ?dhc ?dhcLabel ?university ?universityLabel
        WHERE
        {
          ?dhc wdt:P279 wd:Q11415564.
          ?dhc wdt:P17 wd:Q142.
          ?dhc wdt:P1027 ?university .
          SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
        }');
        } catch (\Exception $exception) {
            dd("Erreur de mise à jour");
        }

        foreach ($result as $row) {
            $universityQid = str_replace("http://www.wikidata.org/entity/", "", $row->university);
            $doctorateQid = str_replace("http://www.wikidata.org/entity/", "", $row->dhc);

            if (!isset($existingUniversities[$universityQid])) {
                $university = new University();
                $university->setQid($universityQid);
                $university->setLabel($row->universityLabel);
                $this->em->persist($university);
                $countCreate++;
            } else {
                $university = $existingUniversities[$universityQid];
                unset($remainingUniversities[$universityQid]);
            }

            if (!isset($existingDoctorates[$doctorateQid])) {
                $dhc = new Doctorate();
                $dhc->addUniversity($university);
                $dhc->setQid($doctorateQid);
                $dhc->setLabel($row->dhcLabel);
                $this->em->persist($dhc);
            } else {
                $dhc = $existingDoctorates[$doctorateQid];
                unset($remainingDoctorates[$doctorateQid]);
            }

            $university->addDoctorate($dhc);
            $this->em->persist($university);
        }

        foreach ($remainingDoctorates as $doctorate) {
            $this->em->remove($doctorate);
        }

        foreach ($remainingUniversities as $university) {
            $this->em->remove($university);
        }

        $this->em->flush();
        return $countCreate;
    }
}