<?php

namespace App\Service\Forum\Admin;

final class FeedbackRow
{
    public function __construct(
        private readonly string $type,
        private readonly string $target,
        private readonly string $decision,
        private readonly string $category,
        private readonly string $toxicity,
        private readonly string $quality,
        private readonly string $duplicate,
        private readonly string $linkThreat,
        private readonly string $fallback,
        private readonly string $moderationNote,
        private readonly \DateTimeInterface $createdAt,
        private readonly ?int $postId = null,
        private readonly ?int $commentId = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getToxicity(): string
    {
        return $this->toxicity;
    }

    public function getQuality(): string
    {
        return $this->quality;
    }

    public function getDuplicate(): string
    {
        return $this->duplicate;
    }

    public function getLinkThreat(): string
    {
        return $this->linkThreat;
    }

    public function getFallback(): string
    {
        return $this->fallback;
    }

    public function getModerationNote(): string
    {
        return $this->moderationNote;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getPostId(): ?int
    {
        return $this->postId;
    }

    public function getCommentId(): ?int
    {
        return $this->commentId;
    }
}
