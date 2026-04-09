<?php

namespace App\Entity;

use App\Repository\EvaluationScoreRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationScoreRepository::class)]
#[ORM\Table(name: 'evaluation_scores')]
class EvaluationScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: InterviewEvaluation::class, inversedBy: 'scores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?InterviewEvaluation $evaluation = null;

    #[ORM\ManyToOne(targetEntity: EvaluationCriteria::class, inversedBy: 'scores')]
    #[ORM\JoinColumn(nullable: false)]
    private ?EvaluationCriteria $criteria = null;

    #[ORM\Column(type: 'float')]
    private ?float $score = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): ?InterviewEvaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(?InterviewEvaluation $evaluation): self
    {
        $this->evaluation = $evaluation;
        return $this;
    }

    public function getCriteria(): ?EvaluationCriteria
    {
        return $this->criteria;
    }

    public function setCriteria(?EvaluationCriteria $criteria): self
    {
        $this->criteria = $criteria;
        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): self
    {
        $this->score = $score;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }
}
