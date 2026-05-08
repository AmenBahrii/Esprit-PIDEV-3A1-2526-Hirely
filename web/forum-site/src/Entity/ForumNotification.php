<?php

namespace App\Entity;

use App\Repository\ForumNotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ForumNotificationRepository::class)]
#[ORM\Table(name: 'forum_notification')]
class ForumNotification
{
    public const TYPE_POST_LIKED = 'POST_LIKED';
    public const TYPE_COMMENT_LIKED = 'COMMENT_LIKED';
    public const TYPE_POST_COMMENTED = 'POST_COMMENTED';
    public const TYPE_POST_STATUS_CHANGED = 'POST_STATUS_CHANGED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'recipient_user_id', referencedColumnName: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private ?User $recipient = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_user_id', referencedColumnName: 'user_id', nullable: true, onDelete: 'SET NULL')]
    private ?User $actor = null;

    #[ORM\Column(type: Types::STRING, length: 40)]
    private string $type = self::TYPE_POST_STATUS_CHANGED;

    #[ORM\ManyToOne(targetEntity: ForumPost::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ForumPost $post = null;

    #[ORM\ManyToOne(targetEntity: ForumComment::class)]
    #[ORM\JoinColumn(name: 'comment_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?ForumComment $comment = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $message = '';

    #[ORM\Column(name: 'is_read', type: Types::BOOLEAN)]
    private bool $isRead = false;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipient(): ?User
    {
        return $this->recipient;
    }

    public function setRecipient(?User $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getActor(): ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor): self
    {
        $this->actor = $actor;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = strtoupper(trim($type));

        return $this;
    }

    public function getPost(): ?ForumPost
    {
        return $this->post;
    }

    public function setPost(?ForumPost $post): self
    {
        $this->post = $post;

        return $this;
    }

    public function getComment(): ?ForumComment
    {
        return $this->comment;
    }

    public function setComment(?ForumComment $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $safe = trim($message);
        if (strlen($safe) > 255) {
            $safe = substr($safe, 0, 252) . '...';
        }
        $this->message = $safe;

        return $this;
    }

    public function isRead(): bool
    {
        return $this->isRead;
    }

    public function setIsRead(bool $isRead): self
    {
        $this->isRead = $isRead;

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
