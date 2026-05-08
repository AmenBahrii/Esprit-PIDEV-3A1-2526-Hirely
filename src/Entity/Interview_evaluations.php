<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Evaluation_scores;

#[ORM\Entity]
class Interview_evaluations
{
    public function __construct()
    {
        $this->evaluation_scores = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $evaluation_id;

        #[ORM\ManyToOne(targetEntity: Interviews::class, inversedBy: "interview_evaluationss")]
    #[ORM\JoinColumn(name: 'interview_id', referencedColumnName: 'interview_id', onDelete: 'CASCADE')]
    private Interviews $interview_id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "interview_evaluationss")]
    #[ORM\JoinColumn(name: 'recruiter_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Users $recruiter_id;

    #[ORM\Column(type: "float")]
    private float $overall_rating;

    #[ORM\Column(type: "string", length: 255)]
    private string $recommendation;

    #[ORM\Column(type: "text")]
    private string $strengths;

    #[ORM\Column(type: "text")]
    private string $weaknesses;

    #[ORM\Column(type: "text")]
    private string $general_comments;

    #[ORM\Column(type: "string", length: 100)]
    private string $hire_decision;

    #[ORM\Column(type: "text")]
    private string $next_steps;

    #[ORM\Column(type: "boolean")]
    private bool $is_draft;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $evaluated_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    public function getEvaluation_id()
    {
        return $this->evaluation_id;
    }

    public function setEvaluation_id($value)
    {
        $this->evaluation_id = $value;
    }

    public function getInterview_id()
    {
        return $this->interview_id;
    }

    public function setInterview_id($value)
    {
        $this->interview_id = $value;
    }

    public function getRecruiter_id()
    {
        return $this->recruiter_id;
    }

    public function setRecruiter_id($value)
    {
        $this->recruiter_id = $value;
    }

    public function getOverall_rating()
    {
        return $this->overall_rating;
    }

    public function setOverall_rating($value)
    {
        $this->overall_rating = $value;
    }

    public function getRecommendation()
    {
        return $this->recommendation;
    }

    public function setRecommendation($value)
    {
        $this->recommendation = $value;
    }

    public function getStrengths()
    {
        return $this->strengths;
    }

    public function setStrengths($value)
    {
        $this->strengths = $value;
    }

    public function getWeaknesses()
    {
        return $this->weaknesses;
    }

    public function setWeaknesses($value)
    {
        $this->weaknesses = $value;
    }

    public function getGeneral_comments()
    {
        return $this->general_comments;
    }

    public function setGeneral_comments($value)
    {
        $this->general_comments = $value;
    }

    public function getHire_decision()
    {
        return $this->hire_decision;
    }

    public function setHire_decision($value)
    {
        $this->hire_decision = $value;
    }

    public function getNext_steps()
    {
        return $this->next_steps;
    }

    public function setNext_steps($value)
    {
        $this->next_steps = $value;
    }

    public function getIs_draft()
    {
        return $this->is_draft;
    }

    public function setIs_draft($value)
    {
        $this->is_draft = $value;
    }

    public function getEvaluated_at()
    {
        return $this->evaluated_at;
    }

    public function setEvaluated_at($value)
    {
        $this->evaluated_at = $value;
    }

    public function getUpdated_at()
    {
        return $this->updated_at;
    }

    public function setUpdated_at($value)
    {
        $this->updated_at = $value;
    }

    #[ORM\OneToMany(mappedBy: "evaluation_id", targetEntity: Evaluation_scores::class)]
    private Collection $evaluation_scores;

    public function getEvaluation_scores()
    {
        return $this->evaluation_scores;
    }

    public function setEvaluation_scores($value)
    {
        $this->evaluation_scores = $value;
    }
}
