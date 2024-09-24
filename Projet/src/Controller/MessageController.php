<?php

// src/Controller/MessageController.php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\UserRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MessageController extends AbstractController
{
    #[Route('/message/new/{recipientId}', name: 'message_new')]
    public function new(
        Request $request, 
        UserRepository $userRepository, 
        EntityManagerInterface $entityManager,  // Injecter l'EntityManager
        int $recipientId
    ): Response {
        // Récupérer le destinataire du message
        $recipient = $userRepository->find($recipientId);
        if (!$recipient) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }
    
        // Créer un nouveau message
        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
    
        // Gérer la soumission du formulaire
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($this->getUser());  // L'utilisateur actuel est l'expéditeur
            $message->setRecipient($recipient);    // Le destinataire est celui trouvé dans l'URL
    
            // Sauvegarder le message en base de données
            $entityManager->persist($message);
            $entityManager->flush();
    
            // Rediriger vers la liste des messages ou une page de succès
            return $this->redirectToRoute('message_list');
        }
    
        // Afficher le formulaire d'envoi de message
        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
            'recipient' => $recipient,  // Transmettre l'utilisateur destinataire à la vue
        ]);}

    #[Route('/messages', name: 'message_list')]
    public function list(MessageRepository $messageRepository): Response
    {
        // Vérifier que l'utilisateur est bien connecté
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos messages.');
        }

        // Récupérer les messages envoyés et reçus par l'utilisateur
        $messagesSent = $messageRepository->findBy(['sender' => $user]);
        $messagesReceived = $messageRepository->findBy(['recipient' => $user]);

        // Afficher la vue avec les messages
        return $this->render('message/list.html.twig', [
            'messagesSent' => $messagesSent,
            'messagesReceived' => $messagesReceived,
        ]);
    }
}
