<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\EvaluationCriteriaRepository;

#[ORM\Entity(repositoryClass: EvaluationCriteriaRepository::class)]
#[ORM\Table(name: 'evaluation_criteria')]
class EvaluationCriteria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $criteria_id = null;

    public function getCriteria_id(): ?int
    {
        return $this->criteria_id;
    }

    public function setCriteria_id(int $criteria_id): self
    {
        $this->criteria_id = $criteria_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $criteria_name = null;

    public function getCriteria_name(): ?string
    {
        return $this->criteria_name;
    }

    public function setCriteria_name(string $criteria_name): self
    {
        $this->criteria_name = $criteria_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $category = null;

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $max_score = null;

    public function getMax_score(): ?int
    {
        return $this->max_score;
    }

    public function setMax_score(?int $max_score): self
    {
        $this->max_score = $max_score;
        return $this;
    }

    #[ORM\Column(type: 'decimal', nullable: true)]
    private ?float $weight = null;

    public function getWeight(): ?float
    {
        return $this->weight;
    }

    public function setWeight(?float $weight): self
    {
        $this->weight = $weight;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: InterviewEvaluation::class, mappedBy: 'evaluationCriteria')]
    private Collection $interviewEvaluations;

    /**
     * @return Collection<int, InterviewEvaluation>
     */
    public function getInterviewEvaluations(): Collection
    {
        if (!$this->interviewEvaluations instanceof Collection) {
            $this->interviewEvaluations = new ArrayCollection();
        }
        return $this->interviewEvaluations;
    }

    public function addInterviewEvaluation(InterviewEvaluation $interviewEvaluation): self
    {
        if (!$this->getInterviewEvaluations()->contains($interviewEvaluation)) {
            $this->getInterviewEvaluations()->add($interviewEvaluation);
        }
        return $this;
    }

    public function removeInterviewEvaluation(InterviewEvaluation $interviewEvaluation): self
    {
        $this->getInterviewEvaluations()->removeElement($interviewEvaluation);
        return $this;
    }

}
