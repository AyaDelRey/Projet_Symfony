<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;



#[Route('/messages')]
class MessageController extends AbstractController
{
    #[Route('/new/{receiverId}', name: 'message_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager, 
        UserRepository $userRepository, 
        $receiverId
    ): Response {
        $message = new Message();
        $receiver = $userRepository->find($receiverId);
        
        if (!$receiver) {
            throw $this->createNotFoundException('User not found');
        }

        // Vérifiez que l'utilisateur est connecté
        $sender = $this->getUser();
        if (!$sender) {
            throw $this->createAccessDeniedException('You must be logged in to send a message.');
        }

        $message->setSender($sender);
        $message->setReceiver($receiver);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('message_list');
        }

        return $this->render('message/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    //#[Route('/', name: 'message_list', methods: ['GET'])]
    //public function list(): Response
    //{
    //    // Vérifiez que l'utilisateur est connecté
    //    $user = $this->getUser();
    //    if (!$user) {
    //        throw $this->createAccessDeniedException('You must be logged in to view messages.');
    //    }
//
    //    // Assurez-vous que la méthode getReceivedMessages() existe dans l'entité User
    //    $messages = $user->getReceivedMessages();
//
    //    return $this->render('message/list.html.twig', [
    //        'messages' => $messages,
    //    ]);
    //}
}
