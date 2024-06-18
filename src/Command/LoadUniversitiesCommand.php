<?php

namespace App\Command;

use App\Entity\Doctorate;
use App\Entity\University;
use Doctrine\ORM\EntityManagerInterface;
use EasyRdf\Sparql\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:load-universities',
    description: 'Import des universités et des doctorats honoris causa associés',
)]
class LoadUniversitiesCommand extends Command
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
        $io->title("Démarrage du chargeur");

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

        print "Chargement initial : ".sizeof($universities)." universités et ".sizeof($doctorates)." doctorats honoris causa\n";

        $sparql = new Client("https://query.wikidata.org/sparql");
        $result = $sparql->query('SELECT ?dhc ?dhcLabel ?university ?universityLabel
        WHERE
        {
          ?dhc wdt:P279 wd:Q11415564.
          ?dhc wdt:P17 wd:Q142.
          ?dhc wdt:P1027 ?university .
          SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
        }');
        foreach ($result as $row) {
            $universityQid = str_replace("http://www.wikidata.org/entity/", "", $row->university);
            $doctorateQid = str_replace("http://www.wikidata.org/entity/", "", $row->dhc);
            if (!isset($existingUniversities[$universityQid])) {
                $university = new University();
                $university->setQid($universityQid);
                $university->setLabel($row->universityLabel);
                $this->em->persist($university);
            } else {
                $university = $existingUniversities[$universityQid];
            }

            if (!isset($existingDoctorates[$doctorateQid])) {
                $dhc = new Doctorate();
                $dhc->setUniversity($university);
                $dhc->setQid($doctorateQid);
                $dhc->setLabel($row->dhcLabel);
                $this->em->persist($dhc);
            } else {
                $dhc = $existingDoctorates[$doctorateQid];
            }

            $university->setDoctorate($dhc);
            $this->em->persist($university);
            $io->writeln($row->dhcLabel);
        }
        $this->em->flush();
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
