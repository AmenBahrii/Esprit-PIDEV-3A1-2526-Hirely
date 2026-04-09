<?php

namespace App\Controller;

use App\Entity\ForumNotification;
use App\Entity\User;
use App\Repository\ForumNotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/open/{id}', name: 'notification_open', requirements: ['id' => '\d+'])]
    public function open(int $id, ForumNotificationRepository $notificationRepository): RedirectResponse
    {
        $viewer = $this->getCurrentUserEntity();
        $notification = $notificationRepository->find($id);

        if (!$notification instanceof ForumNotification || $notification->getRecipient()?->getId() !== $viewer->getId()) {
            return $this->redirectToRoute('app_home');
        }

        $notificationRepository->markReadForRecipient($notification, $viewer);

        $postId = $notification->getPost()?->getId();
        if ($postId !== null) {
            return $this->redirectToRoute('forum_post_show', ['id' => $postId]);
        }

        $actorId = $notification->getActor()?->getId();
        if ($actorId !== null) {
            return $this->redirectToRoute('profile_show', ['id' => $actorId]);
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('/mark-all-read', name: 'notification_mark_all_read', methods: ['POST'])]
    public function markAllRead(Request $request, ForumNotificationRepository $notificationRepository): RedirectResponse
    {
        $viewer = $this->getCurrentUserEntity();

        if ($this->isCsrfTokenValid('mark_all_notifications_read', (string) $request->request->get('_token'))) {
            $notificationRepository->markAllReadForUser($viewer);
        }

        $referer = (string) $request->headers->get('referer', '');
        if ($referer !== '') {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
