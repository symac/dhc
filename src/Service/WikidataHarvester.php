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

class WikidataHarvester
{

    private EntityManagerInterface $em;
    private HttpClientInterface $httpClient;

    private $existingAwards = [];
    private $existingPersons = [];

    private $existingDoctorates = [];

    private string $sparqlQuery;

    public function __construct(EntityManagerInterface $entityManager, httpclientInterface $httpClient)
    {
        $this->em = $entityManager;
        $this->httpClient = $httpClient;
        $this->sparql = new Client("https://query.wikidata.org/sparql");

        # Récupération des awards
        $awards = $this->em->getRepository(Award::class)->findAllWidthDoctorates();

        foreach ($awards as $award) {
            $this->existingAwards[$award->getDoctorate()->getQid()][$award->getPerson()->getQid()] = $award;
        }

        # Récupération des personnes
        $persons = $this->em->getRepository(Person::class)->findAll();

        foreach ($persons as $person) {
            $this->existingPersons[$person->getQid()] = $person;
        }

        # Récupération des doctorats
        $doctorates = $this->em->getRepository(Doctorate::class)->findAll();
        foreach ($doctorates as $doctorate) {
            $this->existingDoctorates[$doctorate->getQid()] = $doctorate;
        }

        print "Pré-chargement : " . sizeof($awards) . " awards existants\n";
    }

    public function setSparqlGlobal()
    {
        $this->sparqlQuery = 'SELECT ?person ?personLabel ?personDescription ?doctorate ?doctorateLabel ?gender ( MIN(?P585base) AS ?P585) (MIN(?P6949base) AS ?P6949) (SAMPLE(?image) as ?image)
        WHERE
        {
          ?doctorate wdt:P279 wd:Q11415564.
          ?doctorate wdt:P17 wd:Q142.
          
          ?person p:P166 ?award.
          ?award ps:P166 ?doctorate .
          
          OPTIONAL {   ?award pq:P585 ?P585base }
          OPTIONAL {   ?award pq:P6949 ?P6949base }
          OPTIONAL {   ?person wdt:P18 ?image }
          OPTIONAL {   ?person wdt:P21 ?gender }
        
          SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
        }

        GROUP BY ?person ?personLabel ?personDescription ?doctorate ?doctorateLabel ?gender';
    }

    public function setSparqlUniversity(University $university)
    {
        $this->sparqlQuery = 'SELECT ?person ?personLabel ?personDescription ?doctorate ?doctorateLabel ?gender ( MIN(?P585base) AS ?P585) (MIN(?P6949base) AS ?P6949) (SAMPLE(?image) as ?image)
        WHERE
        {
          ?person p:P166 ?award.
          ?award ps:P166 wd:' . $university->getDoctorate()->getQid() . ' .
          ?award ps:P166 ?doctorate .
          
          OPTIONAL {   ?award pq:P585 ?P585base }
          OPTIONAL {   ?award pq:P6949 ?P6949base }
          OPTIONAL {   ?person wdt:P18 ?image }
          OPTIONAL {   ?person wdt:P21 ?gender }
        
          SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
        }

        GROUP BY ?person ?personLabel ?personDescription ?doctorate ?doctorateLabel ?gender';
    }

    public function run(): int
    {
        $countCreate = 0;
        $sparql = new Client("https://query.wikidata.org/sparql");
        try {
            $result = $sparql->query($this->sparqlQuery);
        } catch (\Exception $exception) {
            dd("Erreur de mise à jour");
        }

        foreach ($result as $row) {
            $doctorateQid = str_replace("http://www.wikidata.org/entity/", "", $row->doctorate);
            $personQid = str_replace("http://www.wikidata.org/entity/", "", $row->person);
            if (isset($row->gender)) {
                $row->gender = str_replace("http://www.wikidata.org/entity/", "", $row->gender);
            }

            $p585 = null;
            $p6949 = null;

            if (isset($row->P585)) {
                $p585 = $row->P585->getValue();
            }

            if (isset($row->P6949)) {
                $p6949 = $row->P6949->getValue();
            }

            if (isset($this->existingPersons[$personQid])) {
                $person = $this->existingPersons[$personQid];
            } else {
                $person = new Person();
                $person->setQid($personQid);
                $person->setLabel($row->personLabel);
                $this->existingPersons[$personQid] = $person;
            }

            if (isset($row->image)) {
                if ($person->getImage() != str_replace("http://commons.wikimedia.org/wiki/Special:FilePath/", "", $row->image)) {
                    $person->setImage(str_replace("http://commons.wikimedia.org/wiki/Special:FilePath/", "", $row->image));
                    $person->setImageLicense(null);
                    $person->setImageCreator(null);
                }
                $this->em->persist($person);
            }

            if (isset($row->personDescription)) {
                if ($person->getDescription() != $row->personDescription) {
                    $person->setDescription($row->personDescription);
                    $this->em->persist($person);
                }
            }

            if (isset($row->gender)) {
                if ($person->getGender() != $row->gender) {
                    $person->setGender($row->gender);
                    $this->em->persist($person);
                }
            }


            if (isset($this->existingAwards[$doctorateQid][$personQid])) {
                $award = $this->existingAwards[$doctorateQid][$personQid];
            } else {
                if (!isset($this->existingDoctorates[$doctorateQid])) {
                    $doctorate = new Doctorate();
                    $doctorate->setQid($doctorateQid);
                    $doctorate->setLabel($row->doctorateLabel);
                    $this->em->persist($doctorate);

                    $this->existingDoctorates[$doctorateQid] = $doctorate;
                } else {
                    $doctorate = $this->existingDoctorates[$doctorateQid];
                }

                $award = new Award();
                $award->setDoctorate($doctorate);
                $award->setPerson($person);
                $countCreate++;
            }

            if ($p585) {
                $award->setP585($p585);
                $award->setDisplayDate($p585);
            }

            if ($p6949) {
                $award->setP6949($p6949);
                if (!$award->getDisplayDate()) {
                    $award->setDisplayDate($p6949);
                }
            }

            $this->em->persist($award);
        }
        $this->em->flush();
        $this->em->getRepository(Person::class)->updateCount();
        return $countCreate;
    }
}