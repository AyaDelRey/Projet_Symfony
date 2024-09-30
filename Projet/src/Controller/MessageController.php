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
    #[Route('/users', name: 'user_list')]
    public function listUsers(UserRepository $userRepository): Response
    {
        // Exclure l'utilisateur actuel de la liste des utilisateurs
        $users = $userRepository->findAllExceptCurrentUser($this->getUser());

        return $this->render('message/user_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/message/new/{receiverId}', name: 'message_new')]
    public function new(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        int $receiverId
    ): Response {
        // Récupérer le destinataire
        $receiver = $userRepository->find($receiverId);
        if (!$receiver) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        // Empêcher l'utilisateur d'envoyer un message à lui-même
        if ($receiver === $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas vous envoyer un message.');
        }

        // Créer un nouveau message
        $message = new Message();
        $message->setReceiver($receiver);

        $form = $this->createForm(MessageType::class, $message);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $message->setSender($this->getUser()); // L'utilisateur connecté est l'expéditeur
            $message->setSentAt(new \DateTime()); // Enregistrer la date d'envoi

            // Enregistrer le message
            $entityManager->persist($message);
            $entityManager->flush();

            // Ajouter un message flash pour confirmer l'envoi
            $this->addFlash('success', 'Message envoyé avec succès !');

            // Rediriger vers la liste des messages après envoi
            return $this->redirectToRoute('message_list');
        }

        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
            'receiver' => $receiver,
        ]);
    }

    #[Route('/messages', name: 'message_list')]
    public function list(MessageRepository $messageRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour voir vos messages.');
        }
    
        // Récupérer les messages envoyés et reçus
        $messagesSent = $messageRepository->findBy(['sender' => $user]);
        $messagesReceived = $messageRepository->findBy(['receiver' => $user]);
    
        // Regrouper les messages par conversation
        $conversations = [];
    
        foreach ($messagesSent as $message) {
            $receiver = $message->getReceiver();
            $key = $this->getConversationKey($user, $receiver);
            
            if (!isset($conversations[$key])) {
                $conversations[$key] = [
                    'user' => $receiver,
                    'messages' => [],
                ];
            }
            $conversations[$key]['messages'][] = $message;
        }
    
        foreach ($messagesReceived as $message) {
            $sender = $message->getSender();
            $key = $this->getConversationKey($sender, $user);
    
            if (!isset($conversations[$key])) {
                $conversations[$key] = [
                    'user' => $sender,
                    'messages' => [],
                ];
            }
            $conversations[$key]['messages'][] = $message;
        }
    
        return $this->render('message/list.html.twig', [
            'conversations' => $conversations,
        ]);
    }
    
    // Helper function to create a unique key for each conversation
    private function getConversationKey(User $user1, User $user2): string
    {
        return $user1->getId() < $user2->getId() 
            ? $user1->getId() . '-' . $user2->getId() 
            : $user2->getId() . '-' . $user1->getId();
    }

    #[Route('/message/{id}/reply', name: 'message_reply')]
    public function reply(
        Request $request, 
        EntityManagerInterface $entityManager, 
        Message $message
    ): Response {
        // Créer un nouveau message pour la réponse
        $replyMessage = new Message();
        $replyMessage->setReceiver($message->getSender()); // Répondre à l'expéditeur d'origine
        $replyMessage->setContent($message->getContent()); // Optional: set the content if you want to quote

        $form = $this->createForm(MessageType::class, $replyMessage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $replyMessage->setSender($this->getUser()); // L'utilisateur connecté est l'expéditeur
            $replyMessage->setSentAt(new \DateTime()); // Enregistrer la date d'envoi

            // Enregistrer le message de réponse
            $entityManager->persist($replyMessage);
            $entityManager->flush();

            // Ajouter un message flash pour confirmer l'envoi de la réponse
            $this->addFlash('success', 'Réponse envoyée avec succès !');

            // Rediriger vers la liste des messages après envoi
            return $this->redirectToRoute('message_list');
        }

        return $this->render('message/reply.html.twig', [
            'form' => $form->createView(),
            'originalMessage' => $message,
        ]);
    }
}
