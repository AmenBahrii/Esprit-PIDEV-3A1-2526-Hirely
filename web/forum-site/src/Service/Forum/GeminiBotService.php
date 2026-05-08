<?php

namespace App\Service\Forum;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Repository\UserRepository;
use App\Service\Forum\AI\GeminiClient;
use Doctrine\ORM\EntityManagerInterface;

final class GeminiBotService
{
    public function __construct(
        private readonly GeminiClient $geminiClient,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function hasTrigger(string $commentText): bool
    {
        return preg_match('/(?<![A-Za-z0-9._%+-])@gemini\b/i', $commentText) === 1;
    }

    public function shouldReplyToComment(ForumComment $comment): bool
    {
        $authorEmail = strtolower((string) $comment->getAuthor()?->getEmail());
        $status = strtoupper($comment->getStatus());

        return $this->hasTrigger($comment->getContent())
            && ($status === 'APPROVED' || $status === 'PENDING')
            && $comment->getPost() instanceof ForumPost
            && $authorEmail !== 'gemini@hirely.local';
    }

    public function createReplyForComment(ForumComment $sourceComment): ?ForumComment
    {
        if (!$this->shouldReplyToComment($sourceComment)) {
            return null;
        }

        $post = $sourceComment->getPost();
        $botUser = $this->userRepository->findOrCreateSystemUser('gemini@hirely.local', 'Gemini', 'Assistant');
        if (!$post instanceof ForumPost || $post->getId() === null) {
            return null;
        }

        $replyText = $this->geminiClient->generateReply(
            $post->getTitle(),
            $post->getContent(),
            GeminiClient::cleanTriggerToken($sourceComment->getContent()),
            $post->getTag()
        );
        $replyStatus = strtoupper($sourceComment->getStatus()) === 'PENDING' ? 'PENDING' : 'APPROVED';

        $comment = (new ForumComment())
            ->setPost($post)
            ->setAuthor($botUser)
            ->setStatus($replyStatus)
            ->setContent($this->trimToCommentLimit($replyText))
            ->setCreatedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        return $comment;
    }

    private function trimToCommentLimit(string $text): string
    {
        $normalized = trim($text) !== '' ? trim($text) : 'Gemini is unavailable right now (missing key or service error). Please try again later.';
        if (strlen($normalized) <= 1000) {
            return $normalized;
        }

        return substr($normalized, 0, 997) . '...';
    }
}
