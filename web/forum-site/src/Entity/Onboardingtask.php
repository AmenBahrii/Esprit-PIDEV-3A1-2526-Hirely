<?php

namespace App\Entity;

use App\Repository\OnboardingtaskRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OnboardingtaskRepository::class)]
#[ORM\Table(
    name: 'onboardingtask',
    indexes: [
        new ORM\Index(name: 'idx_onboardingtask_plan', columns: ['planId']),
        new ORM\Index(name: 'idx_onboardingtask_plan_task', columns: ['planId', 'taskId']),
        new ORM\Index(name: 'idx_onboardingtask_status_deadline', columns: ['status', 'deadline']),
    ]
)]
class Onboardingtask
{
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_VALUES = [
        self::STATUS_NOT_STARTED,
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_BLOCKED,
        self::STATUS_ON_HOLD,
    ];

    public const STATUS_CHOICES = [
        'Not Started' => self::STATUS_NOT_STARTED,
        'In Progress' => self::STATUS_IN_PROGRESS,
        'Completed' => self::STATUS_COMPLETED,
        'Blocked' => self::STATUS_BLOCKED,
        'On Hold' => self::STATUS_ON_HOLD,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'taskId', type: Types::INTEGER)]
    private ?int $taskId = null;

    #[ORM\ManyToOne(targetEntity: Onboardingplan::class, inversedBy: 'onboardingtasks')]
    #[ORM\JoinColumn(name: 'planId', referencedColumnName: 'planId', nullable: true, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'This task must belong to an onboarding plan.')]
    private ?Onboardingplan $plan = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\Choice(choices: self::STATUS_VALUES, message: 'Please choose a valid task status.')]
    private string $status = self::STATUS_NOT_STARTED;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(name: 'filePath', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $filePath = null;

    #[ORM\Column(name: 'cloudinary_public_id', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $cloudinaryPublicId = null;

    #[ORM\Column(name: 'original_file_name', type: Types::STRING, length: 120, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $originalFileName = null;

    #[ORM\Column(name: 'content_type', type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 120)]
    private ?string $contentType = null;

    public function getTaskId(): ?int
    {
        return $this->taskId;
    }

    public function getPlan(): ?Onboardingplan
    {
        return $this->plan;
    }

    public function setPlan(?Onboardingplan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title !== null ? trim($title) : null;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description !== null ? trim($description) : null;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = trim($status);

        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): self
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath !== null ? trim($filePath) : null;

        return $this;
    }

    public function getCloudinaryPublicId(): ?string
    {
        return $this->cloudinaryPublicId;
    }

    public function setCloudinaryPublicId(?string $cloudinaryPublicId): self
    {
        $this->cloudinaryPublicId = $cloudinaryPublicId !== null ? trim($cloudinaryPublicId) : null;

        return $this;
    }

    public function getOriginalFileName(): ?string
    {
        return $this->originalFileName;
    }

    public function setOriginalFileName(?string $originalFileName): self
    {
        $this->originalFileName = $originalFileName !== null ? trim($originalFileName) : null;

        return $this;
    }

    public function getContentType(): ?string
    {
        return $this->contentType;
    }

    public function setContentType(?string $contentType): self
    {
        $this->contentType = $contentType !== null ? trim($contentType) : null;

        return $this;
    }

    public function hasAttachment(): bool
    {
        return $this->filePath !== null && trim($this->filePath) !== '';
    }

    public function getAttachmentLabel(): string
    {
        if ($this->originalFileName !== null && trim($this->originalFileName) !== '') {
            return $this->originalFileName;
        }

        if ($this->filePath !== null && trim($this->filePath) !== '') {
            $path = parse_url($this->filePath, \PHP_URL_PATH);
            if (is_string($path) && trim($path) !== '') {
                return basename($path);
            }
        }

        return 'Attachment';
    }

    public function getAttachmentPreviewKind(): string
    {
        $contentType = strtolower((string) $this->contentType);
        $extension = strtolower(pathinfo($this->getAttachmentLabel(), \PATHINFO_EXTENSION));

        if (str_starts_with($contentType, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true)) {
            return 'image';
        }

        if ($contentType === 'application/pdf' || $extension === 'pdf') {
            return 'pdf';
        }

        if (str_starts_with($contentType, 'video/') || in_array($extension, ['mp4', 'webm', 'mov', 'avi', 'mkv'], true)) {
            return 'video';
        }

        if (str_starts_with($contentType, 'audio/') || in_array($extension, ['mp3', 'wav', 'ogg', 'm4a'], true)) {
            return 'audio';
        }

        return 'file';
    }

    /**
     * @return array<string, string>
     */
    public static function getStatusChoices(): array
    {
        return self::STATUS_CHOICES;
    }
}
