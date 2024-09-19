<?php
// src/Controller/OeuvreController.php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Form\OeuvreType;
use App\Repository\OeuvreRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/oeuvres')]
class OeuvreController extends AbstractController
{
    #[Route('/', name: 'oeuvre_index', methods: ['GET'])]
    public function index(OeuvreRepository $oeuvreRepository): Response
    {
        $oeuvres = $oeuvreRepository->findAll();

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
        ]);
    }

    #[Route('/oeuvre/new', name: 'oeuvre_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserInterface $user): Response
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle file upload
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                // Move the file to the directory where images are stored
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'), // Utilisation du paramètre
                        $newFilename
                    );
                    
                } catch (FileException $e) {
                    // Optionnel : Ajouter un message d'erreur
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                    return $this->redirectToRoute('oeuvre_new');
                }

                // Update the 'image' property to store the image file name
                $oeuvre->setImage($newFilename);
            }

            // Assigner l'auteur à l'œuvre
            $oeuvre->setAuthor($user);

            // Save the oeuvre
            $entityManager->persist($oeuvre);
            $entityManager->flush();

            // Redirect to some route (for example, the list of œuvres)
            return $this->redirectToRoute('app_user_oeuvres');
        }

        return $this->render('oeuvre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-oeuvres', name: 'app_user_oeuvres')]
    public function userOeuvres(OeuvreRepository $oeuvreRepository, FavoriteRepository $favoriteRepository, UserInterface $user): Response
    {
        // Récupère les œuvres postées par l'utilisateur connecté
        $userOeuvres = $oeuvreRepository->findBy(['author' => $user]);

        // Récupère les œuvres mises en favoris par l'utilisateur connecté
        $favoriteOeuvres = $favoriteRepository->findBy(['user' => $user]);

        return $this->render('oeuvre/user_oeuvres.html.twig', [
            'userOeuvres' => $userOeuvres,
            'favoriteOeuvres' => $favoriteOeuvres,
        ]);
    }
}
