<?php

namespace App\Entity;

use App\Repository\ForumInteractionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumInteractionRepository::class)]
#[ORM\Table(name: 'forum_interaction')]
#[ORM\UniqueConstraint(name: 'uq_interaction', columns: ['target_type', 'target_id', 'user_id', 'interaction_type'])]
class ForumInteraction
{
    public const TARGET_POST = 'POST';
    public const TARGET_COMMENT = 'COMMENT';
    public const TYPE_LIKE = 'LIKE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\Column(name: 'target_type', length: 20)]
    private string $targetType = self::TARGET_POST;

    #[ORM\Column(name: 'target_id', type: Types::BIGINT)]
    private int $targetId = 0;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'interaction_type', length: 20)]
    private string $interactionType = self::TYPE_LIKE;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): self
    {
        $this->targetType = strtoupper(trim($targetType));

        return $this;
    }

    public function getTargetId(): int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): self
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getInteractionType(): string
    {
        return $this->interactionType;
    }

    public function setInteractionType(string $interactionType): self
    {
        $this->interactionType = strtoupper(trim($interactionType));

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
