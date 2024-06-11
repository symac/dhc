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

#[AsCommand(
    name: 'app:load-awards',
    description: 'Import des doctorats décernés',
)]
class LoadAwards extends Command
{

    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Démarrage du chargeur des awards");

        # Récupération des awards
        $awards = $this->em->getRepository(Award::class)->findAll();
        $existingAwards = [];
        foreach ($awards as $award) {
            $existingAwards[$award->getDoctorate()->getQid()][$award->getPerson()->getQid()] = $award;
        }

        print "Pré-chargement : " . sizeof($awards) . " awards existants\n";

        $sparql = new Client("https://query.wikidata.org/sparql");
        $result = $sparql->query('SELECT ?person ?personLabel ?doctorate ?doctorateLabel ?P585 ?P6949
        WHERE
        {
          ?doctorate wdt:P279 wd:Q11415564.
          ?doctorate wdt:P17 wd:Q142.
          
          ?person p:P166 ?award.
          ?award ps:P166 ?doctorate .
          
          OPTIONAL {   ?award pq:P585 ?P585 }
          OPTIONAL {   ?award pq:P6949 ?P6949 }
          
        
          SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
        }');

        $doctorates = $this->em->getRepository(Doctorate::class)->findAll();
        $existingDoctorates = [];
        foreach ($doctorates as $doctorate) {
            $existingDoctorates[$doctorate->getQid()] = $doctorate;
        }

        $count = 0;
        foreach ($result as $row) {
            $doctorateQid = str_replace("http://www.wikidata.org/entity/", "", $row->doctorate);
            $personQid = str_replace("http://www.wikidata.org/entity/", "", $row->person);


            $p585 = null;
            $p6949 = null;

            if (isset($row->P585)) {
                $p585 = $row->P585->getValue()  ;
            }

            if (isset($row->P6949)) {
                $p6949 = $row->P6949->getValue();
            }

            if (isset($existingAwards[$doctorateQid][$personQid])) {
                $award = $existingAwards[$doctorateQid][$personQid];
                $award->setP585($p585);
                $award->setP6949($p6949);
                $award->setDisplayDate($p585);
                $this->em->persist($award);

                continue;
            } else {
                $person = $this->em->getRepository(Person::class)->findOneBy(['qid' => $personQid]);
                if (!$person) {
                    $person = new Person();
                    $person->setQid($personQid);
                    $person->setLabel($row->personLabel);
                    $this->em->persist($person);
                }

                if (!isset($existingDoctorates[$doctorateQid])) {
                    $doctorate = new Doctorate();
                    $doctorate->setQid($doctorateQid);
                    $doctorate->setLabel($row->doctorateLabel);
                    $this->em->persist($doctorate);
                } else {
                    $doctorate = $existingDoctorates[$doctorateQid];
                }

                $award = new Award();
                $award->setDoctorate($doctorate);
                $award->setPerson($person);
                $this->em->persist($award);
                $count++;
            }
        }
        $this->em->flush();
        $io->success('Mise à jour terminée, création de '.$count.' awards');

        return Command::SUCCESS;
    }
}
