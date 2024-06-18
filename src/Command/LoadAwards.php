<?php

namespace App\Command;

use App\Entity\Award;
use App\Entity\Doctorate;
use App\Entity\Person;
use App\Entity\University;
use Doctrine\ORM\EntityManagerInterface;
use EasyRdf\Sparql\Client;
use PhpParser\ErrorHandler\Collecting;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:load-awards',
    description: 'Import des doctorats décernés',
)]
class LoadAwards extends Command
{

    private $em;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->em = $entityManager;
        $this->httpClient = $httpClient;
        parent::__construct();
    }


    protected function configure(): void
    {
        $this
            ->addOption('reset', null, InputOption::VALUE_NONE, 'nettoyage de la table avant réimport');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Démarrage du chargeur des awards");;



        # Récupération des awards
        $awards = $this->em->getRepository(Award::class)->findAll();
        $existingAwards = [];
        foreach ($awards as $award) {
            $existingAwards[$award->getDoctorate()->getQid()][$award->getPerson()->getQid()] = $award;
        }

        # Récupération des personnes
        $persons = $this->em->getRepository(Person::class)->findAll();
        $existingPersons = [];
        foreach ($persons as $person) {
            $existingPersons[$person->getQid()] = $person;
        }

        print "Pré-chargement : " . sizeof($awards) . " awards existants\n";

        $sparql = new Client("https://query.wikidata.org/sparql");
        $result = $sparql->query('SELECT ?person ?personLabel ?personDescription ?doctorate ?doctorateLabel ?gender ( MIN(?P585base) AS ?P585) (MIN(?P6949base) AS ?P6949) (SAMPLE(?image) as ?image)
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

        GROUP BY ?person ?personLabel ?personDescription ?doctorate ?doctorateLabel ?gender');

        $doctorates = $this->em->getRepository(Doctorate::class)->findAll();
        $existingDoctorates = [];
        foreach ($doctorates as $doctorate) {
            $existingDoctorates[$doctorate->getQid()] = $doctorate;
        }

        $countCreate = 0;
        foreach ($result as $row) {
            $doctorateQid = str_replace("http://www.wikidata.org/entity/", "", $row->doctorate);
            $personQid = str_replace("http://www.wikidata.org/entity/", "", $row->person);

            $p585 = null;
            $p6949 = null;

            if (isset($row->P585)) {
                $p585 = $row->P585->getValue();
            }

            if (isset($row->P6949)) {
                $p6949 = $row->P6949->getValue();
            }

            if (isset($existingPersons[$personQid])) {
                $person = $existingPersons[$personQid];
            } else {
                $person = new Person();
                $person->setQid($personQid);
                $person->setLabel($row->personLabel);
                $this->em->persist($person);
                $existingPersons[$personQid] = $person;
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
                $person->setDescription($row->personDescription);
            }

            if (isset($row->gender)) {
                $person->setGender(str_replace("http://www.wikidata.org/entity/", "", $row->gender));
            }

            if (isset($existingAwards[$doctorateQid][$personQid])) {
                $award = $existingAwards[$doctorateQid][$personQid];
            } else {


                if (!isset($existingDoctorates[$doctorateQid])) {
                    $doctorate = new Doctorate();
                    $doctorate->setQid($doctorateQid);
                    $doctorate->setLabel($row->doctorateLabel);
                    $this->em->persist($doctorate);

                    $existingDoctorates[$doctorateQid] = $doctorate;
                } else {
                    $doctorate = $existingDoctorates[$doctorateQid];
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
        $io->success('Mise à jour terminée, création de ' . $countCreate . ' awards');

        return Command::SUCCESS;
    }
}
