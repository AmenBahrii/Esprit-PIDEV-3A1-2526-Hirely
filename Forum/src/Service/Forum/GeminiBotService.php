<?php

namespace App\Service\Forum;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Entity\Users;
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
        return str_contains(strtolower($commentText), '@gemini');
    }

    public function createReplyForComment(ForumPost $post, string $userComment): ?ForumComment
    {
        $botUser = $this->userRepository->findOrCreateSystemUser('gemini@hirely.local', 'Gemini', 'Assistant');
        if (!$botUser instanceof Users || $post->getId() === null) {
            return null;
        }

        $replyText = $this->geminiClient->generateReply(
            $post->getTitle(),
            $post->getContent(),
            GeminiClient::cleanTriggerToken($userComment),
            $post->getTag()
        );

        $comment = (new ForumComment())
            ->setPost($post)
            ->setAuthor($botUser)
            ->setStatus('APPROVED')
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



