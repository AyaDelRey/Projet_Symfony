<?php
// src/Controller/ConversationController.php

namespace App\Controller;

use App\Entity\Conversation;
use App\Repository\ConversationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConversationController extends AbstractController
{
    #[Route('/conversation/{id}', name: 'conversation_show')]
    public function show(Conversation $conversation): Response
    {
        $messages = $conversation->getMessages();

        return $this->render('conversation/show.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }
}
