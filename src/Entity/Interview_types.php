<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Interviews;

#[ORM\Entity]
class Interview_types
{
    public function __construct()
    {
        $this->interviewss = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $interview_type_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $type_name;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "integer")]
    private int $typical_duration_minutes;

    #[ORM\Column(type: "boolean")]
    private bool $is_active;

    public function getInterview_type_id()
    {
        return $this->interview_type_id;
    }

    public function setInterview_type_id($value)
    {
        $this->interview_type_id = $value;
    }

    public function getType_name()
    {
        return $this->type_name;
    }

    public function setType_name($value)
    {
        $this->type_name = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getTypical_duration_minutes()
    {
        return $this->typical_duration_minutes;
    }

    public function setTypical_duration_minutes($value)
    {
        $this->typical_duration_minutes = $value;
    }

    public function getIs_active()
    {
        return $this->is_active;
    }

    public function setIs_active($value)
    {
        $this->is_active = $value;
    }

    #[ORM\OneToMany(mappedBy: "interview_type_id", targetEntity: Interviews::class)]
    private Collection $interviewss;

        public function getInterviewss(): Collection
        {
            return $this->interviewss;
        }
    
        public function addInterviews(Interviews $interviews): self
        {
            if (!$this->interviewss->contains($interviews)) {
                $this->interviewss[] = $interviews;
                $interviews->setInterview_type_id($this);
            }
    
            return $this;
        }
    
        public function removeInterviews(Interviews $interviews): self
        {
            if ($this->interviewss->removeElement($interviews)) {
                // set the owning side to null (unless already changed)
                if ($interviews->getInterview_type_id() === $this) {
                    $interviews->setInterview_type_id(null);
                }
            }
    
            return $this;
        }
}
