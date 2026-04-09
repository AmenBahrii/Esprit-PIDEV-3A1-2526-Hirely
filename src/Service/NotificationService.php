<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createNotification(User $user, string $message, string $type = 'info'): Notification
    {
        $notification = new Notification();
        $notification->setUser($user);
        $notification->setMessage($message);
        $notification->setType($type);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();

        return $notification;
    }

    public function getUnreadNotifications(User $user)
    {
        return $this->notificationRepository->findUnreadByUser($user);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->setRead(true);
        $this->entityManager->flush();
    }
}
