<?php

namespace App\Entity;

<<<<<<< HEAD
use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'applications')]
=======
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\ApplicationRepository;

#[ORM\Entity(repositoryClass: ApplicationRepository::class)]
#[ORM\Table(name: 'application')]
>>>>>>> OnboardingCoordination
class Application
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
<<<<<<< HEAD
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $jobId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $candidateName = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $status = 'applied';

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'application')]
    private Collection $interviews;

    public function __construct()
    {
        $this->interviews = new ArrayCollection();
        $this->createdAt = new \DateTime();
=======
    #[ORM\Column(name: 'applicationId', type: 'integer')]
    private ?int $applicationId = null;

    public function getApplicationId(): ?int
    {
        return $this->applicationId;
>>>>>>> OnboardingCoordination
    }

    public function getId(): ?int
    {
<<<<<<< HEAD
        return $this->id;
    }

    public function getJobId(): ?string
    {
        return $this->jobId;
    }

    public function setJobId(?string $jobId): self
    {
        $this->jobId = $jobId;
        return $this;
    }

    public function getCandidateName(): ?string
    {
        return $this->candidateName;
    }

    public function setCandidateName(string $candidateName): self
    {
        $this->candidateName = $candidateName;
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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function addInterview(Interview $interview): self
    {
        if (!$this->interviews->contains($interview)) {
            $this->interviews->add($interview);
            $interview->setApplication($this);
        }
        return $this;
    }
=======
        return $this->applicationId;
    }

    public function setApplicationId(int $applicationId): self
    {
        $this->applicationId = $applicationId;
        return $this;
    }

    public function setId(int $id): self
    {
        $this->applicationId = $id;

        return $this;
    }

    #[ORM\Column(name: 'applicationDate', type: 'date', nullable: true)]
    private ?\DateTimeInterface $applicationDate = null;

    public function getApplicationDate(): ?\DateTimeInterface
    {
        return $this->applicationDate;
    }

    public function setApplicationDate(?\DateTimeInterface $applicationDate): self
    {
        $this->applicationDate = $applicationDate;
        return $this;
    }

    #[ORM\Column(name: 'coverLetter', type: 'text', nullable: true)]
    private ?string $coverLetter = null;

    public function getCoverLetter(): ?string
    {
        return $this->coverLetter;
    }

    public function setCoverLetter(?string $coverLetter): self
    {
        $this->coverLetter = $coverLetter;
        return $this;
    }

    #[ORM\Column(name: 'currentStatus', type: 'string', nullable: true)]
    private ?string $currentStatus = null;

    public function getCurrentStatus(): ?string
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus(?string $currentStatus): self
    {
        $this->currentStatus = $currentStatus;
        return $this;
    }

    #[ORM\Column(name: 'resumePath', type: 'string', nullable: true)]
    private ?string $resumePath = null;

    public function getResumePath(): ?string
    {
        return $this->resumePath;
    }

    public function setResumePath(?string $resumePath): self
    {
        $this->resumePath = $resumePath;
        return $this;
    }

    #[ORM\Column(name: 'lastUpdateDate', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUpdateDate = null;

    public function getLastUpdateDate(): ?\DateTimeInterface
    {
        return $this->lastUpdateDate;
    }

    public function setLastUpdateDate(?\DateTimeInterface $lastUpdateDate): self
    {
        $this->lastUpdateDate = $lastUpdateDate;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Joboffer::class, inversedBy: 'applications')]
    #[ORM\JoinColumn(name: 'jobOfferId', referencedColumnName: 'jobOfferId')]
    private ?Joboffer $joboffer = null;

    public function getJoboffer(): ?Joboffer
    {
        return $this->joboffer;
    }

    public function setJoboffer(?Joboffer $joboffer): self
    {
        $this->joboffer = $joboffer;
        return $this;
    }

    #[ORM\Column(name: 'expectedSalary', type: 'decimal', nullable: true)]
    private ?string $expectedSalary = null;

    public function getExpectedSalary(): ?string
    {
        return $this->expectedSalary;
    }

    public function setExpectedSalary(?string $expectedSalary): self
    {
        $this->expectedSalary = $expectedSalary;
        return $this;
    }

    #[ORM\Column(name: 'availabilityDate', type: 'date', nullable: true)]
    private ?\DateTimeInterface $availabilityDate = null;

    public function getAvailabilityDate(): ?\DateTimeInterface
    {
        return $this->availabilityDate;
    }

    public function setAvailabilityDate(?\DateTimeInterface $availabilityDate): self
    {
        $this->availabilityDate = $availabilityDate;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $phone = null;

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(name: 'experienceYears', type: 'integer', nullable: true)]
    private ?int $experienceYears = null;

    public function getExperienceYears(): ?int
    {
        return $this->experienceYears;
    }

    public function setExperienceYears(?int $experienceYears): self
    {
        $this->experienceYears = $experienceYears;
        return $this;
    }

    #[ORM\Column(name: 'portfolioUrl', type: 'string', nullable: true)]
    private ?string $portfolioUrl = null;

    public function getPortfolioUrl(): ?string
    {
        return $this->portfolioUrl;
    }

    public function setPortfolioUrl(?string $portfolioUrl): self
    {
        $this->portfolioUrl = $portfolioUrl;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?string $score = null;

    public function getScore(): ?string
    {
        return $this->score;
    }

    public function setScore(?string $score): self
    {
        $this->score = $score;
        return $this;
    }

    #[ORM\Column(name: 'reviewNote', type: 'text', nullable: true)]
    private ?string $reviewNote = null;

    public function getReviewNote(): ?string
    {
        return $this->reviewNote;
    }

    public function setReviewNote(?string $reviewNote): self
    {
        $this->reviewNote = $reviewNote;
        return $this;
    }

>>>>>>> OnboardingCoordination
}
