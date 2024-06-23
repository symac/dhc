<?php

namespace App\Command;

use App\Service\WikidataUniversityHarvester;
use Doctrine\ORM\EntityManagerInterface;
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
    private $harvester;

    public function __construct(EntityManagerInterface $entityManager, WikidataUniversityHarvester $harvester)
    {
        $this->em = $entityManager;
        $this->harvester = $harvester;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Démarrage du chargeur");


        $countCreate = $this->harvester->run();
        $io->success('Mise à jour terminé ('.$countCreate.' créations).');

        return Command::SUCCESS;
    }
}
