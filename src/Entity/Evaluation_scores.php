<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Evaluation_criteria;

#[ORM\Entity]
class Evaluation_scores
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $score_id;

        #[ORM\ManyToOne(targetEntity: Interview_evaluations::class, inversedBy: "evaluation_scoress")]
    #[ORM\JoinColumn(name: 'evaluation_id', referencedColumnName: 'evaluation_id', onDelete: 'CASCADE')]
    private Interview_evaluations $evaluation_id;

        #[ORM\ManyToOne(targetEntity: Evaluation_criteria::class, inversedBy: "evaluation_scoress")]
    #[ORM\JoinColumn(name: 'criteria_id', referencedColumnName: 'criteria_id', onDelete: 'CASCADE')]
    private Evaluation_criteria $criteria_id;

    #[ORM\Column(type: "integer")]
    private int $score;

    #[ORM\Column(type: "text")]
    private string $comments;

    public function getScore_id()
    {
        return $this->score_id;
    }

    public function setScore_id($value)
    {
        $this->score_id = $value;
    }

    public function getEvaluation_id()
    {
        return $this->evaluation_id;
    }

    public function setEvaluation_id($value)
    {
        $this->evaluation_id = $value;
    }

    public function getCriteria_id()
    {
        return $this->criteria_id;
    }

    public function setCriteria_id($value)
    {
        $this->criteria_id = $value;
    }

    public function getScore()
    {
        return $this->score;
    }

    public function setScore($value)
    {
        $this->score = $value;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setComments($value)
    {
        $this->comments = $value;
    }
}
