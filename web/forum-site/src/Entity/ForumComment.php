<?php

namespace App\Entity;

use App\Repository\ForumCommentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ForumCommentRepository::class)]
#[ORM\Table(name: 'forum_comment')]
class ForumComment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ForumPost::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?ForumPost $post = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'authoredComments')]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'user_id', nullable: false)]
    private ?User $author = null;

    #[Assert\NotBlank(message: 'Comment is required.', normalizer: 'trim')]
    #[Assert\Length(min: 2, max: 1000, minMessage: 'Comment must be between 2 and 1000 characters.', maxMessage: 'Comment must be between 2 and 1000 characters.')]
    #[Assert\Regex(pattern: '/\S/', message: 'Comment cannot be empty or spaces only.')]
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[Assert\Choice(choices: ['APPROVED', 'PENDING', 'REJECTED'], message: 'Invalid status selected.')]
    #[ORM\Column(length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(name: 'moderation_note', type: Types::TEXT, nullable: true)]
    private ?string $moderationNote = null;

    #[ORM\Column(name: 'edited_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $editedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'editedComments')]
    #[ORM\JoinColumn(name: 'edited_by', referencedColumnName: 'user_id', nullable: true)]
    private ?User $editedBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'is_pinned', type: Types::BOOLEAN)]
    private bool $isPinned = false;

    private int $likeCount = 0;
    private bool $likedByCurrentUser = false;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = trim($content);

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = strtoupper(trim($status));

        return $this;
    }

    public function getModerationNote(): ?string
    {
        return $this->moderationNote;
    }

    public function setModerationNote(?string $moderationNote): self
    {
        $this->moderationNote = $moderationNote;

        return $this;
    }

    public function getEditedAt(): ?\DateTimeInterface
    {
        return $this->editedAt;
    }

    public function setEditedAt(?\DateTimeInterface $editedAt): self
    {
        $this->editedAt = $editedAt;

        return $this;
    }

    public function getEditedBy(): ?User
    {
        return $this->editedBy;
    }

    public function setEditedBy(?User $editedBy): self
    {
        $this->editedBy = $editedBy;

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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): self
    {
        $this->isPinned = $isPinned;

        return $this;
    }

    public function getLikeCount(): int
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount): self
    {
        $this->likeCount = max(0, $likeCount);

        return $this;
    }

    public function isLikedByCurrentUser(): bool
    {
        return $this->likedByCurrentUser;
    }

    public function setLikedByCurrentUser(bool $likedByCurrentUser): self
    {
        $this->likedByCurrentUser = $likedByCurrentUser;

        return $this;
    }
}
