<?php

namespace App\Twig;

use App\Entity\User;
use App\Repository\ForumNotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ForumNotificationExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly ForumNotificationRepository $notificationRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('forum_notification_menu_data', [$this, 'getMenuData']),
        ];
    }

    public function getMenuData(int $limit = 10): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [
                'unread_count' => 0,
                'notifications' => [],
            ];
        }

        try {
            $notifications = $this->notificationRepository->findLatestForUser($user, max(1, $limit));
            $unreadCount = $this->notificationRepository->countUnreadForUser($user);
        } catch (\Throwable) {
            return [
                'unread_count' => 0,
                'notifications' => [],
            ];
        }

        $items = [];
        foreach ($notifications as $notification) {
            $items[] = [
                'id' => $notification->getId(),
                'message' => $notification->getMessage(),
                'is_read' => $notification->isRead(),
                'created_at' => $notification->getCreatedAt(),
                'type' => $notification->getType(),
                'open_url' => $this->urlGenerator->generate('notification_open', ['id' => $notification->getId()]),
            ];
        }

        return [
            'unread_count' => $unreadCount,
            'notifications' => $items,
        ];
    }
}
