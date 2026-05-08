<?php

namespace App\Service\Forum;

use App\Entity\ForumComment;
use App\Entity\ForumNotification;
use App\Entity\ForumPost;
use App\Entity\User;
use App\Repository\ForumNotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    private const LIKE_COOLDOWN_MINUTES = 24 * 60;

    public function __construct(
        private readonly ForumNotificationRepository $notificationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function notifyPostLiked(ForumPost $post, User $actor): void
    {
        $recipient = $post->getAuthor();
        if (!$recipient instanceof User || $this->shouldSkip($actor, $recipient)) {
            return;
        }

        if ($this->notificationRepository->hasRecentLikeNotification(
            $recipient,
            $actor,
            ForumNotification::TYPE_POST_LIKED,
            $post,
            null,
            self::LIKE_COOLDOWN_MINUTES
        )) {
            return;
        }

        $this->createNotification(
            $recipient,
            $actor,
            ForumNotification::TYPE_POST_LIKED,
            sprintf('%s liked your post (#%d).', $actor->getDisplayName(), (int) $post->getId()),
            $post,
            null
        );
    }

    public function notifyCommentLiked(ForumComment $comment, User $actor): void
    {
        $recipient = $comment->getAuthor();
        $post = $comment->getPost();
        if (!$recipient instanceof User || !$post instanceof ForumPost || $this->shouldSkip($actor, $recipient)) {
            return;
        }

        if ($this->notificationRepository->hasRecentLikeNotification(
            $recipient,
            $actor,
            ForumNotification::TYPE_COMMENT_LIKED,
            $post,
            $comment,
            self::LIKE_COOLDOWN_MINUTES
        )) {
            return;
        }

        $this->createNotification(
            $recipient,
            $actor,
            ForumNotification::TYPE_COMMENT_LIKED,
            sprintf('%s liked your comment (#%d) on post #%d.', $actor->getDisplayName(), (int) $comment->getId(), (int) $post->getId()),
            $post,
            $comment
        );
    }

    public function notifyPostCommented(ForumPost $post, ForumComment $comment, User $actor): void
    {
        $recipient = $post->getAuthor();
        if (!$recipient instanceof User || $this->shouldSkip($actor, $recipient)) {
            return;
        }

        $this->createNotification(
            $recipient,
            $actor,
            ForumNotification::TYPE_POST_COMMENTED,
            sprintf('%s commented on your post (#%d).', $actor->getDisplayName(), (int) $post->getId()),
            $post,
            $comment
        );
    }

    public function notifyPostStatusChanged(ForumPost $post, User $admin, string $oldStatus): void
    {
        $recipient = $post->getAuthor();
        $newStatus = strtoupper($post->getStatus());
        if (
            !$recipient instanceof User
            || $this->shouldSkip($admin, $recipient)
            || strtoupper($oldStatus) === $newStatus
        ) {
            return;
        }

        $this->createNotification(
            $recipient,
            $admin,
            ForumNotification::TYPE_POST_STATUS_CHANGED,
            sprintf('Admin updated your post (#%d) status to %s.', (int) $post->getId(), $newStatus),
            $post,
            null
        );
    }

    public function notifyCommentStatusChanged(ForumComment $comment, User $admin, string $oldStatus): void
    {
        $recipient = $comment->getAuthor();
        $post = $comment->getPost();
        $newStatus = strtoupper($comment->getStatus());
        if (
            !$recipient instanceof User
            || !$post instanceof ForumPost
            || $this->shouldSkip($admin, $recipient)
            || strtoupper($oldStatus) === $newStatus
        ) {
            return;
        }

        $this->createNotification(
            $recipient,
            $admin,
            ForumNotification::TYPE_POST_STATUS_CHANGED,
            sprintf(
                'Admin updated your comment (#%d) on post #%d to %s.',
                (int) $comment->getId(),
                (int) $post->getId(),
                $newStatus
            ),
            $post,
            $comment
        );
    }

    private function createNotification(
        User $recipient,
        ?User $actor,
        string $type,
        string $message,
        ?ForumPost $post,
        ?ForumComment $comment
    ): void {
        $notification = (new ForumNotification())
            ->setRecipient($recipient)
            ->setActor($actor)
            ->setType($type)
            ->setMessage($message)
            ->setPost($post)
            ->setComment($comment)
            ->setIsRead(false)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    private function shouldSkip(User $actor, User $recipient): bool
    {
        return $actor->getId() === null
            || $recipient->getId() === null
            || $actor->getId() === $recipient->getId();
    }
}
