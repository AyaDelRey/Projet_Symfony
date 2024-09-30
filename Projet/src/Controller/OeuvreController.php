<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\Comment;
use App\Form\OeuvreType;
use App\Form\CommentType;
use App\Repository\OeuvreRepository;
use App\Repository\CommentRepository;
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
    #[Route('/', name: 'oeuvre_index', methods: ['GET', 'POST'])]
    public function index(Request $request, OeuvreRepository $oeuvreRepository, FavoriteRepository $favoriteRepository, UserInterface $user, CommentRepository $commentRepository): Response
    {
        // Création du formulaire pour ajouter un commentaire
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
    
        // Gestion de la soumission du formulaire de commentaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $oeuvreId = $request->request->get('oeuvre_id'); // Obtenir l'ID de l'œuvre
    
            // Trouver l'œuvre associée
            $oeuvre = $oeuvreRepository->find($oeuvreId);
            if ($oeuvre) {
                $comment->setUser($user); // Associer l'utilisateur connecté
                $comment->setOeuvre($oeuvre); // Associer le commentaire à l'œuvre
                $commentRepository->save($comment, true); // Enregistrer le commentaire
            }
    
            return $this->redirectToRoute('oeuvre_index'); // Rediriger après l'ajout
        }
    
        // Gestion de la recherche AJAX
        if ($request->isXmlHttpRequest()) {
            $keyword = $request->query->get('keyword');
            $titre = $request->query->get('titre');
            $artiste = $request->query->get('artiste');
            $year = $request->query->get('year');
            $type = $request->query->get('type');
            $technique = $request->query->get('technique');
            $lieu_creation = $request->query->get('lieu_creation');
            $dimensions = $request->query->get('dimensions');
            $mouvement = $request->query->get('mouvement');
            $collection = $request->query->get('collection');
    
            // Rechercher les œuvres selon les filtres
            $oeuvres = $oeuvreRepository->findByFilters(
                $keyword,
                $titre,
                $artiste,
                $year,
                $type,
                $technique,
                $lieu_creation,
                $dimensions,
                $mouvement,
                $collection
            );
    
            // Créer une réponse JSON pour les œuvres
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
                    'comments' => $oeuvre->getComments(), // Récupération des commentaires
                ];
            }
    
            return new JsonResponse($oeuvresData);
        }
    
        // Récupérer toutes les œuvres avec leurs commentaires
        $oeuvres = $oeuvreRepository->findAll();
    
        // Récupérer les œuvres favorites de l'utilisateur
        $favoriteOeuvres = $favoriteRepository->findBy(['user' => $user]);
    
        return $this->render('oeuvre/index.html.twig', [
            'oeuvres' => $oeuvres,
            'favoriteOeuvres' => $favoriteOeuvres,
            'comment_form' => $form->createView(), // Passer le formulaire au template
        ]);
    }
    

    #[Route('/new', name: 'oeuvre_new', methods: ['GET', 'POST'])]
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
                    return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de l\'upload de l\'image.'], 500);
                }
            }

            $oeuvre->setAuthor($user);

            if ($oeuvre->getDate()) {
                $year = $oeuvre->getDate()->format('Y');
                $dateTime = \DateTime::createFromFormat('Y', $year);
                $oeuvre->setDate($dateTime);
            }

            $entityManager->persist($oeuvre);
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Œuvre ajoutée avec succès!',
                'redirect' => $this->generateUrl('oeuvre_show', ['id' => $oeuvre->getId()])
            ], 200);
        }

        if ($request->isXmlHttpRequest()) {
            $errors = [];
            foreach ($form->getErrors(true, true) as $error) {
                $errors[] = $error->getMessage();
            }

            return new JsonResponse(['status' => 'error', 'errors' => $errors], 400);
        }

        return $this->render('oeuvre/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/mes-oeuvres', name: 'app_user_oeuvres', methods: ['GET'])]
    public function userOeuvres(OeuvreRepository $oeuvreRepository, FavoriteRepository $favoriteRepository, UserInterface $user): Response
    {
        $userOeuvres = $oeuvreRepository->findBy(['author' => $user]);
        $favoriteOeuvres = $favoriteRepository->findBy(['user' => $user]);

        return $this->render('oeuvre/user_oeuvres.html.twig', [
            'userOeuvres' => $userOeuvres,
            'favoriteOeuvres' => $favoriteOeuvres,
        ]);
    }

    #[Route('/{id}', name: 'oeuvre_show', methods: ['GET', 'POST'])]
    public function show(Oeuvre $oeuvre, Request $request, EntityManagerInterface $entityManager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        // Vérifier si la requête est AJAX
        if ($request->isXmlHttpRequest() && $form->isSubmitted() && $form->isValid()) {
            $comment->setOeuvre($oeuvre);
            $comment->setUser($this->getUser());
            $comment->setCreatedAt(new \DateTime());

            $entityManager->persist($comment);
            $entityManager->flush();

            return new JsonResponse([
                'status' => 'success',
                'comment' => [
                    'contenu' => $comment->getContenu(),
                    'user' => [
                        'username' => $this->getUser()->getUserIdentifier(),
                    ],
                    'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'), // Date de création
                ],
            ]);
        }

        // Afficher la vue si ce n'est pas une requête AJAX
        return $this->render('oeuvre/show.html.twig', [
            'oeuvre' => $oeuvre,
            'comment_form' => $form->createView(),
            'comments' => $oeuvre->getComments(),
        ]);
    }

    #[Route('/commentaire/new', name: 'commentaire_new', methods: ['POST'])]
    public function newComment(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $contenu = $request->request->get('contenu');
        $oeuvreId = $request->request->get('oeuvreId');

        if ($user && $contenu) {
            $oeuvre = $entityManager->getRepository(Oeuvre::class)->find($oeuvreId);

            if ($oeuvre) {
                $comment = new Comment();
                $comment->setContenu($contenu);
                $comment->setOeuvre($oeuvre);
                $comment->setUser($user);
                $comment->setCreatedAt(new \DateTime());

                $entityManager->persist($comment);
                $entityManager->flush();

                return new JsonResponse([
                    'comment' => [
                        'contenu' => $comment->getContenu(),
                        'user' => [
                            'username' => $user->getUserIdentifier(),
                        ],
                        'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s'), // Date de création
                    ],
                ]);
            }
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Erreur lors de l\'ajout du commentaire.'], 400);
    }
}
