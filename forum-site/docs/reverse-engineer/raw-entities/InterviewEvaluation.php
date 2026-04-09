<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\InterviewEvaluationRepository;

#[ORM\Entity(repositoryClass: InterviewEvaluationRepository::class)]
#[ORM\Table(name: 'interview_evaluations')]
class InterviewEvaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $evaluation_id = null;

    public function getEvaluation_id(): ?int
    {
        return $this->evaluation_id;
    }

    public function setEvaluation_id(int $evaluation_id): self
    {
        $this->evaluation_id = $evaluation_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Interview::class, inversedBy: 'interviewEvaluations')]
    #[ORM\JoinColumn(name: 'interview_id', referencedColumnName: 'interview_id')]
    private ?Interview $interview = null;

    public function getInterview(): ?Interview
    {
        return $this->interview;
    }

    public function setInterview(?Interview $interview): self
    {
        $this->interview = $interview;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: EvaluationCriteria::class, inversedBy: 'interviewEvaluations')]
    #[ORM\JoinColumn(name: 'criteria_id', referencedColumnName: 'criteria_id')]
    private ?EvaluationCriteria $evaluationCriteria = null;

    public function getEvaluationCriteria(): ?EvaluationCriteria
    {
        return $this->evaluationCriteria;
    }

    public function setEvaluationCriteria(?EvaluationCriteria $evaluationCriteria): self
    {
        $this->evaluationCriteria = $evaluationCriteria;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $score = null;

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comments = null;

    public function getComments(): ?string
    {
        return $this->comments;
    }

    public function setComments(?string $comments): self
    {
        $this->comments = $comments;
        return $this;
    }

}
