<?php

namespace App\Controller;

use App\Entity\Award;
use App\Entity\Person;
use App\Entity\University;
use App\Service\WikidataHarvester;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

        return $this->render('index.html.twig', [
            'universities' => $universities,
            'genderGap' => $genderGap,
            'yearStats' => $yearStats,
            'years' => $years,
            'countDhc' => $countDhc,
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
