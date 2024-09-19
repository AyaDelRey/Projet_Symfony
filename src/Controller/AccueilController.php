<?php

namespace App\Controller;

use App\Repository\OeuvreRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AccueilController extends AbstractController
{


    #[Route('/accueil', name: 'app_accueil')]
    public function index(OeuvreRepository $oeuvreRepository): Response
    {

        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED') === false) {
            $this->addFlash('success', 'Vous avez été déconnecté avec succès.');
        }
        // Récupère les 5 dernières œuvres ajoutées
        $oeuvres = $oeuvreRepository->findBy([], ['date' => 'DESC'], 5);

        return $this->render('accueil/index.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }
}
