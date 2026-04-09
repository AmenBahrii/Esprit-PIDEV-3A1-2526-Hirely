<?php

namespace App\Entity;

use App\Repository\InterviewRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
#[ORM\Table(name: 'interviews')]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Application $application = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recruiter = null;

    #[ORM\ManyToOne(targetEntity: InterviewType::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?InterviewType $interviewType = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $scheduleDate = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $duration = null; // in minutes

    #[ORM\Column(type: 'string', length: 50)]
    private string $format = 'virtual'; // 'virtual' or 'in-person'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    private ?string $meetingLink = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'scheduled'; // 'scheduled', 'completed', 'cancelled'

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $round = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: InterviewEvaluation::class, mappedBy: 'interview')]
    private Collection $evaluations;

    public function __construct()
    {
        $this->evaluations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): self
    {
        $this->application = $application;
        return $this;
    }

    public function getRecruiter(): ?User
    {
        return $this->recruiter;
    }

    public function setRecruiter(?User $recruiter): self
    {
        $this->recruiter = $recruiter;
        return $this;
    }

    public function getInterviewType(): ?InterviewType
    {
        return $this->interviewType;
    }

    public function setInterviewType(?InterviewType $interviewType): self
    {
        $this->interviewType = $interviewType;
        return $this;
    }

    public function getScheduleDate(): ?\DateTimeInterface
    {
        return $this->scheduleDate;
    }

    public function setScheduleDate(\DateTimeInterface $scheduleDate): self
    {
        $this->scheduleDate = $scheduleDate;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): self
    {
        $this->duration = $duration;
        return $this;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getMeetingLink(): ?string
    {
        return $this->meetingLink;
    }

    public function setMeetingLink(?string $meetingLink): self
    {
        $this->meetingLink = $meetingLink;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getRound(): ?int
    {
        return $this->round;
    }

    public function setRound(?int $round): self
    {
        $this->round = $round;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }
}
