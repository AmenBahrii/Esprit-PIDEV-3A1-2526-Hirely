<?php

namespace App\Entity;

use App\Repository\InterviewEvaluationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InterviewEvaluationRepository::class)]
#[ORM\Table(name: 'interview_evaluations')]
class InterviewEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Interview::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Interview $interview = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'evaluations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $recruiter = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $overallRating = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $recommendation = null; // 'strongly_recommend', 'recommend', 'neutral', 'not_recommend'

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $hireDecision = null; // 'hire', 'no_hire', 'maybe', 'pending'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $strengths = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $weaknesses = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $nextSteps = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(targetEntity: EvaluationScore::class, mappedBy: 'evaluation', cascade: ['persist', 'remove'])]
    private Collection $scores;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInterview(): ?Interview
    {
        return $this->interview;
    }

    public function setInterview(?Interview $interview): self
    {
        $this->interview = $interview;
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

    public function getOverallRating(): ?float
    {
        return $this->overallRating;
    }

    public function setOverallRating(?float $overallRating): self
    {
        $this->overallRating = $overallRating;
        return $this;
    }

    public function getRecommendation(): ?string
    {
        return $this->recommendation;
    }

    public function setRecommendation(?string $recommendation): self
    {
        $this->recommendation = $recommendation;
        return $this;
    }

    public function getHireDecision(): ?string
    {
        return $this->hireDecision;
    }

    public function setHireDecision(?string $hireDecision): self
    {
        $this->hireDecision = $hireDecision;
        return $this;
    }

    public function getStrengths(): ?string
    {
        return $this->strengths;
    }

    public function setStrengths(?string $strengths): self
    {
        $this->strengths = $strengths;
        return $this;
    }

    public function getWeaknesses(): ?string
    {
        return $this->weaknesses;
    }

    public function setWeaknesses(?string $weaknesses): self
    {
        $this->weaknesses = $weaknesses;
        return $this;
    }

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;
        return $this;
    }

    public function getNextSteps(): ?string
    {
        return $this->nextSteps;
    }

    public function setNextSteps(?string $nextSteps): self
    {
        $this->nextSteps = $nextSteps;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(EvaluationScore $score): self
    {
        if (!$this->scores->contains($score)) {
            $this->scores->add($score);
            $score->setEvaluation($this);
        }
        return $this;
    }
}
