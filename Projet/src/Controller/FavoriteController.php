<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\Favorite;
use App\Repository\FavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavoriteController extends AbstractController
{
    #[Route('/oeuvre/{id}/favori', name: 'favori_toggle', methods: ['POST'])]
    public function toggleFavori(Oeuvre $oeuvre, EntityManagerInterface $em, FavoriteRepository $favoriteRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }
    
        $favorite = $favoriteRepository->findOneBy([
            'user' => $user,
            'oeuvre' => $oeuvre,
        ]);
    
        if ($favorite) {
            // Si l'œuvre est déjà en favori, la retirer
            $em->remove($favorite);
            $em->flush();
    
            return $this->json(['message' => 'Œuvre retirée des favoris'], Response::HTTP_OK);
        } else {
            // Si l'œuvre n'est pas en favori, l'ajouter
            $newFavorite = new Favorite();
            $newFavorite->setUser($user);
            $newFavorite->setOeuvre($oeuvre);
            $newFavorite->setCreatedAt(new \DateTime());
    
            $em->persist($newFavorite);
            $em->flush();
    
            return $this->json(['message' => 'Œuvre ajoutée aux favoris'], Response::HTTP_CREATED);
        }
    }
    
}
