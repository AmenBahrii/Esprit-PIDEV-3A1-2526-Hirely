<?php

namespace App\Entity;

use App\Repository\ForumPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ForumPostRepository::class)]
#[ORM\Table(name: 'forum_post')]
class ForumPost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'author_id', referencedColumnName: 'user_id', nullable: false)]
    private ?Users $author = null;

    #[Assert\NotBlank(message: 'Title is required.', normalizer: 'trim')]
    #[Assert\Length(min: 3, max: 80, minMessage: 'Title must be between 3 and 80 characters.', maxMessage: 'Title must be between 3 and 80 characters.')]
    #[Assert\Regex(pattern: '/\S/', message: 'Title cannot be empty or spaces only.')]
    #[ORM\Column(length: 255)]
    private string $title = '';

    #[Assert\NotBlank(message: 'Content is required.', normalizer: 'trim')]
    #[Assert\Length(min: 10, max: 5000, minMessage: 'Content must be between 10 and 5000 characters.', maxMessage: 'Content must be between 10 and 5000 characters.')]
    #[Assert\Regex(pattern: '/\S/', message: 'Content cannot be empty or spaces only.')]
    #[ORM\Column(type: Types::TEXT)]
    private string $content = '';

    #[Assert\Length(min: 2, max: 30, minMessage: 'Tag must be between 2 and 30 characters.', maxMessage: 'Tag must be between 2 and 30 characters.')]
    #[Assert\Regex(pattern: '/^#[A-Za-z0-9_-]{1,29}$/', message: 'Tag must start with # and contain only letters, numbers, _ or -.')]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $tag = null;

    #[Assert\Choice(choices: ['APPROVED', 'PENDING', 'REJECTED'], message: 'Invalid status selected.')]
    #[ORM\Column(length: 20)]
    private string $status = 'PENDING';

    #[ORM\Column(name: 'is_pinned', type: Types::BOOLEAN)]
    private bool $isPinned = false;

    #[ORM\Column(name: 'is_locked', type: Types::BOOLEAN)]
    private bool $isLocked = false;

    #[ORM\Column(name: 'moderation_note', type: Types::TEXT, nullable: true)]
    private ?string $moderationNote = null;

    #[ORM\Column(name: 'edited_at', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $editedAt = null;

    #[ORM\ManyToOne(targetEntity: Users::class)]
    #[ORM\JoinColumn(name: 'edited_by', referencedColumnName: 'user_id', nullable: true)]
    private ?Users $editedBy = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: ForumComment::class, orphanRemoval: true)]
    #[ORM\OrderBy(['isPinned' => 'DESC', 'createdAt' => 'DESC'])]
    private Collection $comments;

    private int $likeCount = 0;
    private int $commentCount = 0;
    private bool $likedByCurrentUser = false;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?Users
    {
        return $this->author;
    }

    public function setAuthor(?Users $author): self
    {
        $this->author = $author;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

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

    public function getTag(): ?string
    {
        return $this->tag;
    }

    public function setTag(?string $tag): self
    {
        $tag = $tag !== null ? trim($tag) : null;
        $tag = $tag !== null ? preg_replace('/\s+/', '', $tag) : null;
        if ($tag !== null && $tag !== '' && $tag[0] !== '#') {
            $tag = '#' . $tag;
        }

        $this->tag = $tag !== '' ? $tag : null;

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

    public function isPinned(): bool
    {
        return $this->isPinned;
    }

    public function setIsPinned(bool $isPinned): self
    {
        $this->isPinned = $isPinned;

        return $this;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setIsLocked(bool $isLocked): self
    {
        $this->isLocked = $isLocked;

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

    public function getEditedBy(): ?Users
    {
        return $this->editedBy;
    }

    public function setEditedBy(?Users $editedBy): self
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

    public function getComments(): Collection
    {
        return $this->comments;
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

    public function getCommentCount(): int
    {
        return $this->commentCount;
    }

    public function setCommentCount(int $commentCount): self
    {
        $this->commentCount = max(0, $commentCount);

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




