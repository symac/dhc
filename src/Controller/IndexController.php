<?php

namespace App\Controller;

use App\Entity\University;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    #[Route('/', name: 'app_index')]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $universities = $entityManager->getRepository(University::class)->findBy([], ['label' => 'ASC']);

        return $this->render('index.html.twig', [
            'universities' => $universities,
        ]);
    }

    #[Route('/etablissement/{qid}-{slug}', name: 'app_university')]
    public function university(string $qid, EntityManagerInterface $entityManager): Response
    {
        $university = $entityManager->getRepository(University::class)->findOneBy(['qid' => $qid]);

        if (!$university) {
            throw $this->createNotFoundException('Etablissement non trouvé');
        }

        return $this->render('university.html.twig', [
            'university' => $university,
        ]);
    }
}
