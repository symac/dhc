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
    name: 'app:load-images',
    description: 'Import des informations sur les images',
)]
class LoadImages extends Command
{

    private $em;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        $this->em = $entityManager;
        $this->httpClient = $httpClient;
        parent::__construct();
    }

    private function enrichPersonImageMetadata(Person $person): Person
    {
        $url = "https://commons.wikimedia.org/w/api.php?action=query&prop=imageinfo&format=json&iiprop=extmetadata&iilimit=10&titles=File:" . $person->getImage() . "&iiextmetadatalanguage=fr";
        $response = $this->httpClient->request('GET', $url);
        $data = json_decode($response->getContent());
        $pages = $data->query->pages;
        $page = reset($pages);

        if (isset($page->{"imageinfo"}[0]->{"extmetadata"}->{"Artist"})) {
            $creator = $page->{"imageinfo"}[0]->{"extmetadata"}->{"Artist"}->{"value"};
            if (preg_match('~\x{00a0}<span class="mw-valign-text-top" typeof="mw:File/Frameless">~siu', $creator)) {
                $creator = preg_replace('~\x{00a0}.*$~siu', '', $creator);
            }
            $person->setImageCreator($creator);
        }

        $license = $page->{"imageinfo"}[0]->{"extmetadata"}->{"LicenseShortName"}->{"value"};

        $person->setImageLicense($license);
        return $person;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Démarrage du chargeur des images");;

        $persons = $this->em->getRepository(Person::class)->findImagesNeedUpdate();
        $countCreate = 0;
        $io->newLine();

        foreach ($persons as $person) {
            $person = $this->enrichPersonImageMetadata($person);
            $this->em->persist($person);

            $countCreate++;

            if (!($countCreate % 10)) {
                $this->em->flush();
                print $countCreate." mises à jour\n";
            }
        }
        $this->em->flush();
        $io->success('Mise à jour terminée, création de ' . $countCreate . ' awards');

        return Command::SUCCESS;
    }
}
