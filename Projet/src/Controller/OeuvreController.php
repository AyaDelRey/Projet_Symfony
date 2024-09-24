<?php
// src/Controller/OeuvreController.php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\Comment;
use App\Form\OeuvreType;
use App\Form\CommentType;
use App\Repository\OeuvreRepository;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/oeuvres')]
class OeuvreController extends AbstractController
{
    #[Route('/', name: 'oeuvre_index', methods: ['GET'])]
    public function index(Request $request, OeuvreRepository $oeuvreRepository, FavoriteRepository $favoriteRepository, UserInterface $user): Response
    {
        // Si la requête est une requête AJAX (pour la recherche dynamique)
        if ($request->isXmlHttpRequest()) {
            $keyword = $request->query->get('keyword');
            $artiste = $request->query->get('artiste');
            $year = $request->query->get('year');
            $type = $request->query->get('type');
            $technique = $request->query->get('technique');
            $lieuCreation = $request->query->get('lieu_creation');
            $dimensions = $request->query->get('dimensions');
            $mouvement = $request->query->get('mouvement');
            $collection = $request->query->get('collection');

            // Appelle le repository pour trouver les œuvres qui correspondent aux critères
            $oeuvres = $oeuvreRepository->findByFilters($keyword, $artiste, $year, $type, $technique, $lieuCreation, $dimensions, $mouvement, $collection);

            // Prépare les données pour la réponse JSON
            $oeuvresData = [];
            foreach ($oeuvres as $oeuvre) {
                $oeuvresData[] = [
                    'id' => $oeuvre->getId(),
                    'titre' => $oeuvre->getTitre(),
                    'artiste' => $oeuvre->getArtiste(),
                    'date' => $oeuvre->getDate() ? $oeuvre->getDate()->format('Y-m-d') : null,
                    'type' => $oeuvre->getType(),
                    'technique' => $oeuvre->getTechnique(),
                    'lieu_creation' => $oeuvre->getLieuCreation(),
                    'dimensions' => $oeuvre->getDimensions(),
                    'mouvement' => $oeuvre->getMouvement(),
                    'collection' => $oeuvre->getCollection(),
                    'image' => $oeuvre->getImage(),
                ];
            }

            return new JsonResponse($oeuvresData);
        }

        // Récupérer toutes les œuvres pour l'affichage classique
        $oeuvres = $oeuvreRepository->findAll();

        // Récupérer les œuvres mises en favoris par l'utilisateur
        $favoriteOeuvres = $favoriteRepository->findBy(['user' => $user]);

        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
            'favoriteOeuvres' => $favoriteOeuvres,
        ]);
    }

    #[Route('/oeuvre/new', name: 'oeuvre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, UserInterface $user): Response
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    $oeuvre->setImage($newFilename);
                } catch (FileException $e) {
                    return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de l\'upload du fichier image.'], 500);
                }
            }

            $oeuvre->setAuthor($user);
            $entityManager->persist($oeuvre);
            $entityManager->flush();

            // Retourne une réponse JSON en cas de succès
            return new JsonResponse([
                'status' => 'success',
                'message' => 'Œuvre ajoutée avec succès!',
                'redirect' => $this->generateUrl('oeuvre_show', ['id' => $oeuvre->getId()])
            ], 200);
        }

        // Si la requête est AJAX mais que le formulaire est invalide, on retourne les erreurs en JSON
        if ($request->isXmlHttpRequest()) {
            $errors = [];
            foreach ($form->getErrors(true, true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse(['status' => 'error', 'errors' => $errors], 400);
        }

        // Pour les requêtes non AJAX (affichage classique du formulaire)
        return $this->render('oeuvre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-oeuvres', name: 'app_user_oeuvres')]
    public function userOeuvres(OeuvreRepository $oeuvreRepository, FavoriteRepository $favoriteRepository, UserInterface $user): Response
    {
        // Récupérer les œuvres postées par l'utilisateur connecté
        $userOeuvres = $oeuvreRepository->findBy(['author' => $user]);

        // Récupérer les œuvres mises en favoris par l'utilisateur
        $favoriteOeuvres = $favoriteRepository->findBy(['user' => $user]);

        return $this->render('oeuvre/user_oeuvres.html.twig', [
            'userOeuvres' => $userOeuvres,
            'favoriteOeuvres' => $favoriteOeuvres,
        ]);
    }

    #[Route('/oeuvre/{id}', name: 'oeuvre_show', methods: ['GET', 'POST'])]
    public function show(Oeuvre $oeuvre, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setOeuvre($oeuvre);
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            // Redirection pour éviter la resoumission du formulaire
            return $this->redirectToRoute('oeuvre_show', ['id' => $oeuvre->getId()]);
        }

        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
            'form' => $form->createView(),
            'comments' => $oeuvre->getComments(),
        ]);
    }

    #[Route('/commentaire/new', name: 'commentaire_new', methods: ['POST'])]
    public function newComment(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $content = $request->request->get('content');
        $oeuvreId = $request->request->get('oeuvreId');

        if ($user && $content) {
            $oeuvre = $entityManager->getRepository(Oeuvre::class)->find($oeuvreId);
            
            if ($oeuvre) {
                $comment = new Comment();
                $comment->setContenu($content);
                $comment->setOeuvre($oeuvre);
                $comment->setUser($user);
                $comment->setCreatedAt(new \DateTime());

                $entityManager->persist($comment);
                $entityManager->flush();

                // Retourner une réponse JSON pour le commentaire nouvellement créé
                return new JsonResponse([
                    'comment' => [
                        'contenu' => $comment->getContenu(),
                        'user' => [
                            'username' => $user->getUserIdentifier(),
                        ],
                    ],
                ]);
            }
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du commentaire.'], 400);
    }
}
