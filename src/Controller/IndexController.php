<?php

namespace App\Controller;

use App\Entity\Award;
use App\Entity\Country;
use App\Entity\Person;
use App\Entity\University;
use App\Service\WikidataHarvester;
use Doctrine\ORM\EntityManagerInterface;
use EasyRdf\Sparql\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Symfony\Component\String\u;

class IndexController extends AbstractController
{
    private function enrichGenderGapResult(array $genderGap): array {
        $total = 0;
        foreach ($genderGap as $id => $gender) {
            $total += $gender["nb"];
        };
        foreach ($genderGap as $id => $gender) {
            $genderGap[$id]["genderLabel"] = $this->genderMapLabel($gender["gender"])." (".sprintf("%0.2f", (($gender["nb"] / $total) * 100))." %)";
            $genderGap[$id]["genderColour"] = $this->genderMapColour($gender["gender"]);
        }
        return $genderGap;
    }
    public static function genderMapLabel($qid): string
    {
        if (is_null($qid)) {
            return "genre non spécifié sur wikidata";
        } elseif ($qid == "Q48270") {
            return "non-binaire";
        } elseif ($qid == "Q6581072") {
            return "féminin";
        } elseif ($qid == "Q6581097") {
            return "masculin";
        }

        return "genre inconnu (".$qid.")";
    }

    public static function genderMapColour($qid): string
    {
        if (is_null($qid)) {
            return "#CCC";
        } elseif ($qid == "Q48270") {
            return "#3c7272";
        } elseif ($qid == "Q6581072") {
            return "#9966ff";
        } elseif ($qid == "Q6581097") {
            return "#ff9f40";
        }
        return "#CCC";
    }

    #[Route('/', name: 'app_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $universities = $entityManager->getRepository(University::class)->findAllForIndex();

        $genderGap = $entityManager->getRepository(Person::class)->getGenderStats();
        $genderGap = $this->enrichGenderGapResult($genderGap);

        $yearStats = $entityManager->getRepository(Person::class)->getYearStats();
        $countDhc = $entityManager->getRepository(Award::class)->count();
        $years = [];
        foreach ($yearStats as $gender => $stats) {
            foreach ($stats["stats"] as $year => $value) {
                $years[$year] = $year;
            }
        }

        $countries = $entityManager->getRepository(Country::class)->findAll();
        // Serait mieux de faire directement ces tris au niveau de la base de données
        // mais pas trouvé de solution simple sous SQLite.
        uasort($countries, function ($a, $b) {
            return strcmp(u($a->getLabel())->ascii()->upper(), u($b->getLabel())->ascii()->upper());
        });

        uasort($universities, function ($a, $b) {
            return strcmp(u($a->getLabel())->ascii()->upper(), u($b->getLabel())->ascii()->upper());
        });



        return $this->render('index.html.twig', [
            'universities' => $universities,
            'genderGap' => $genderGap,
            'yearStats' => $yearStats,
            'years' => $years,
            'countDhc' => $countDhc,
            'colorMale' => $this::genderMapColour("Q6581097"),
            'colorFemale' => $this::genderMapColour("Q6581072"),
            'countries' => $countries
        ]);
    }

    #[Route('/etablissement/{qid}-{slug}', name: 'app_university')]
    public function university(string $qid, EntityManagerInterface $entityManager): Response
    {
        $university = $entityManager->getRepository(University::class)->findOneByQid($qid);

        $genderGap = $entityManager->getRepository(University::class)->getGenderStats($university);
        $genderGap = $this->enrichGenderGapResult($genderGap);
        if (!$university) {
            throw $this->createNotFoundException('Etablissement non trouvé');
        }

        return $this->render('university.html.twig', [
            'university' => $university,
            'genderGap' => $genderGap,
        ]);
    }

    #[Route('/pays/{slug}/{qid}', name: 'app_country_detail')]
    public function country(EntityManagerInterface $entityManager, string $qid): Response {
        $country = $entityManager->getRepository(Country::class)->findCountryWithDetail($qid);
        return $this->render('country.html.twig', [
            'country' => $country,
        ]);
    }



    #[Route('/refresh-all', name: 'app_refresh_all')]
    public function refreshAll(WikidataHarvester $wikidataHarvester): Response {
        $wikidataHarvester->setSparqlGlobal();
        $countCreate = $wikidataHarvester->run();

        $this->addFlash('success', "Mise à jour effectuée avec succès (création de $countCreate récompenses). Si vous avez fait récemment des modifications sur wikidata non reflétées ici, c'est peut-être lié au délai de mise à jour du serveur SPARQL de wikidata. Réessayer d'ici quelques minutes.");

        return $this->redirectToRoute('app_index');

    }

    #[Route('/etablissement/{qid}-{slug}/refresh', name: 'app_university_refresh')]
    public function universityRefresh(string $qid, string $slug, EntityManagerInterface $entityManager, WikidataHarvester $wikidataHarvester): Response
    {
        $university = $entityManager->getRepository(University::class)->findOneByQid($qid);

        $wikidataHarvester->setSparqlUniversity($university);
        $countCreate = $wikidataHarvester->run();

        $this->addFlash('success', "Mise à jour effectuée avec succès. Si vous avez fait récemment des modifications sur wikidata non reflétées ici, c'est peut-être lié au délai de mise à jour du serveur SPARQL de wikidata. Réessayer d'ici quelques minutes.");

        return $this->redirectToRoute('app_university', ['qid' => $qid, 'slug' => $slug]);
    }



    #[Route('/personne/{qid}-{slug}', name: 'app_person')]
    public function person(string $qid, EntityManagerInterface $entityManager): Response
    {
        $person = $entityManager->getRepository(Person::class)->findOneByQid($qid);

        if (!$person) {
            throw $this->createNotFoundException('Personne non trouvée');
        }
        return $this->render('person.html.twig', [
            'person' => $person,
        ]);
    }

    #[Route('/update-countries', name: 'app_update_countries')]
    public function updateCountries(EntityManagerInterface $entityManager): Response {
        $sparql = new Client("https://query.wikidata.org/sparql");
        $result = $sparql->query('SELECT ?person ?country ?countryLabel (MIN(?flag) AS ?finalFlag)
        WHERE
        {
          ?doctorate wdt:P279 wd:Q11415564.
          ?doctorate wdt:P17 wd:Q142.
        
          ?person p:P166 ?award.
          ?award ps:P166 ?doctorate .
          ?person wdt:P27 ?country
          MINUS {
            ?person wdt:P27 ?country .
            FILTER wikibase:isSomeValue(?country)
          }
          OPTIONAL {
            ?country wdt:P41 ?flag
          }
          SERVICE wikibase:label { bd:serviceParam wikibase:language "fr,en". }
        }
        GROUP BY  ?person ?country ?countryLabel');
        $persons = $entityManager->getRepository(Person::class)->findAll();
        $existingPersons = [];
        foreach ($persons as $person) {
            $existingPersons[$person->getQid()] = $person;
        }

        $countries = $entityManager->getRepository(Country::class)->findAll();
        $existingCountries = [];
        foreach ($countries as $country) {
            $existingCountries[$country->getQid()] = $country;
        }

        foreach ($result as $row) {
            $row->person = str_replace("http://www.wikidata.org/entity/", "", $row->person);
            $row->country = str_replace("http://www.wikidata.org/entity/", "", $row->country);
            $row->countryLabel = (string) $row->countryLabel;

            if (!isset($existingCountries[$row->country])) {
                $country = new Country();
                $country->setQid($row->country);
                $country->setLabel($row->countryLabel);
                $entityManager->persist($country);

                $existingCountries[$row->country] = $country;
            } else {
                $country = $existingCountries[$row->country];
            }

            if (isset($row->finalFlag)) {
                $row->finalFlag = str_replace("http://commons.wikimedia.org/wiki/Special:FilePath/", "", $row->finalFlag);
                if ($country->getFlag() != $row->finalFlag) {
                    $country->setFlag($row->finalFlag);
                    $entityManager->persist($country);
                    $existingCountries[$row->country] = $country;
                }
            }

            $person = $existingPersons[$row->person];
            $countCountries = $person->getCountries()->count();
            $person->addCountry($country);
            if ($countCountries != $person->getCountries()->count()) {
                $entityManager->persist($person);
            }
        }
        $entityManager->flush();
        return new Response("Mise à jour ok");
    }


    // Créer une route pour nettoyer le cache ( clear cache )
    #[Route('/clear-cache', name: 'app_clear_cache')]
    public function clearCache(): Response
    {
        // On récupère le cache
        $cache = $this->getParameter('kernel.cache_dir');

        // empty dir
        $filesystem = new Filesystem();
        $filesystem->remove($cache);

        // On redirige vers la page d'accueil
        print "Ok";
        return new Response("");
    }
}
