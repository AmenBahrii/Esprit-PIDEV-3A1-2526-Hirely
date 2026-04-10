<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Evaluation_scores;

#[ORM\Entity]
class Evaluation_criteria
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $criteria_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $criteria_name;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $max_score;

    #[ORM\Column(type: "float")]
    private float $weight;

    #[ORM\Column(type: "string", length: 100)]
    private string $category;

    #[ORM\Column(type: "boolean")]
    private bool $is_active;

    #[ORM\Column(type: "integer")]
    private int $display_order;

    public function getCriteria_id()
    {
        return $this->criteria_id;
    }

    public function setCriteria_id($value)
    {
        $this->criteria_id = $value;
    }

    public function getCriteria_name()
    {
        return $this->criteria_name;
    }

    public function setCriteria_name($value)
    {
        $this->criteria_name = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getMax_score()
    {
        return $this->max_score;
    }

    public function setMax_score($value)
    {
        $this->max_score = $value;
    }

    public function getWeight()
    {
        return $this->weight;
    }

    public function setWeight($value)
    {
        $this->weight = $value;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function setCategory($value)
    {
        $this->category = $value;
    }

    public function getIs_active()
    {
        return $this->is_active;
    }

    public function setIs_active($value)
    {
        $this->is_active = $value;
    }

    public function getDisplay_order()
    {
        return $this->display_order;
    }

    public function setDisplay_order($value)
    {
        $this->display_order = $value;
    }

    #[ORM\OneToMany(mappedBy: "criteria_id", targetEntity: Evaluation_scores::class)]
    private Collection $evaluation_scoress;

        public function getEvaluation_scoress(): Collection
        {
            return $this->evaluation_scoress;
        }
    
        public function addEvaluation_scores(Evaluation_scores $evaluation_scores): self
        {
            if (!$this->evaluation_scoress->contains($evaluation_scores)) {
                $this->evaluation_scoress[] = $evaluation_scores;
                $evaluation_scores->setCriteria_id($this);
            }
    
            return $this;
        }
    
        public function removeEvaluation_scores(Evaluation_scores $evaluation_scores): self
        {
            if ($this->evaluation_scoress->removeElement($evaluation_scores)) {
                // set the owning side to null (unless already changed)
                if ($evaluation_scores->getCriteria_id() === $this) {
                    $evaluation_scores->setCriteria_id(null);
                }
            }
    
            return $this;
        }
}
