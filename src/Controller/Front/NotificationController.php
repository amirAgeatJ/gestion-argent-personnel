<?php

declare(strict_types=1);

namespace App\Controller\Front;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
final class NotificationController extends AbstractController
{
    #[Route(name: 'app_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('front/notification/index.html.twig', [
            'notifications' => $notificationRepository->findRecentForUser($user, 50),
        ]);
    }

    #[Route('/{id}/lue', name: 'app_notification_mark_read', methods: ['POST'])]
    public function markRead(Notification $notification, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($notification->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('read'.$notification->getId(), $request->getPayload()->getString('_token'))) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_notification_index');
    }
}
