<?php

namespace App\Controller;

use App\Entity\Award;
use App\Entity\Person;
use App\Entity\University;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
            $genderGap[$id]["genderLabel"] = $this->genderMap($gender["gender"])." (".sprintf("%0.2f", (($gender["nb"] / $total) * 100))." %)";
        }
        return $genderGap;
    }
    public static function genderMap($qid): string
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
            foreach ($stats as $year => $value) {
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
