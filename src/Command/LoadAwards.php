<?php

namespace App\Command;

use App\Service\WikidataHarvester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
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

    private $wikidataHarvester;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient, WikidataHarvester $wikidataHarvester)
    {
        $this->wikidataHarvester = $wikidataHarvester;
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

        $this->wikidataHarvester->setSparqlGlobal();
        $countCreate = $this->wikidataHarvester->run();

        $io->success('Mise à jour terminée, création de ' . $countCreate . ' awards');

        return Command::SUCCESS;
    }
}
