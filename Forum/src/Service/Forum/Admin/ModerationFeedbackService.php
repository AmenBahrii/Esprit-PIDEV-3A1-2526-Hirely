<?php

namespace App\Service\Forum\Admin;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Repository\ForumCommentRepository;
use App\Repository\ForumPostRepository;
use App\Service\Forum\AI\ModerationNoteParser;

final class ModerationFeedbackService
{
    public function __construct(
        private readonly ForumPostRepository $postRepository,
        private readonly ForumCommentRepository $commentRepository,
        private readonly ModerationNoteParser $noteParser,
    ) {
    }

    /**
     * @return list<FeedbackRow>
     */
    public function buildRows(string $filter = 'all', int $limit = 250): array
    {
        $rows = [];
        $normalizedFilter = strtolower(trim($filter));

        if ($normalizedFilter !== 'comments') {
            foreach ($this->postRepository->findLatestWithModerationNotes($limit) as $post) {
                if ($post instanceof ForumPost) {
                    $rows[] = $this->mapPost($post);
                }
            }
        }

        if ($normalizedFilter !== 'posts') {
            foreach ($this->commentRepository->findLatestWithModerationNotes($limit) as $comment) {
                if ($comment instanceof ForumComment) {
                    $rows[] = $this->mapComment($comment);
                }
            }
        }

        usort($rows, static fn (FeedbackRow $left, FeedbackRow $right): int => $right->getCreatedAt()->getTimestamp() <=> $left->getCreatedAt()->getTimestamp());

        return array_slice($rows, 0, $limit);
    }

    private function mapPost(ForumPost $post): FeedbackRow
    {
        $parsed = $this->noteParser->parse($post->getModerationNote());

        return new FeedbackRow(
            'Post',
            'Post #' . $post->getId(),
            $parsed['decision'] ?? strtoupper($post->getStatus()),
            $parsed['category'],
            $parsed['toxicity'] ?? '-',
            $parsed['quality'] ?? '-',
            $parsed['duplicate'] ?? '-',
            $parsed['link_threat'] ?? '-',
            $parsed['fallback'],
            $post->getModerationNote() ?? 'No analysis stored',
            $post->getUpdatedAt() ?? $post->getCreatedAt() ?? new \DateTimeImmutable(),
            $post->getId(),
            null,
        );
    }

    private function mapComment(ForumComment $comment): FeedbackRow
    {
        $parsed = $this->noteParser->parse($comment->getModerationNote());

        return new FeedbackRow(
            'Comment',
            'Comment #' . $comment->getId(),
            $parsed['decision'] ?? strtoupper($comment->getStatus()),
            $parsed['category'],
            $parsed['toxicity'] ?? '-',
            $parsed['quality'] ?? '-',
            $parsed['duplicate'] ?? '-',
            $parsed['link_threat'] ?? '-',
            $parsed['fallback'],
            $comment->getModerationNote() ?? 'No analysis stored',
            $comment->getUpdatedAt() ?? $comment->getCreatedAt() ?? new \DateTimeImmutable(),
            $comment->getPost()?->getId(),
            $comment->getId(),
        );
    }
}


