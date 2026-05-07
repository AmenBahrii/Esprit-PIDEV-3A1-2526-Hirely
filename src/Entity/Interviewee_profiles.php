<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;
use Doctrine\Common\Collections\Collection;
use App\Entity\Interviews;

#[ORM\Entity]
class Interviewee_profiles
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $interviewee_id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "interviewee_profiless")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Users $user_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $first_name;

    #[ORM\Column(type: "string", length: 100)]
    private string $last_name;

    #[ORM\Column(type: "text")]
    private string $skills;

    #[ORM\Column(type: "string", length: 20)]
    private string $phone;

    #[ORM\Column(type: "string", length: 255)]
    private string $linkedin_url;

    #[ORM\Column(type: "string", length: 255)]
    private string $portfolio_url;

    public function getInterviewee_id()
    {
        return $this->interviewee_id;
    }

    public function setInterviewee_id($value)
    {
        $this->interviewee_id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getFirst_name()
    {
        return $this->first_name;
    }

    public function setFirst_name($value)
    {
        $this->first_name = $value;
    }

    public function getLast_name()
    {
        return $this->last_name;
    }

    public function setLast_name($value)
    {
        $this->last_name = $value;
    }

    public function getSkills()
    {
        return $this->skills;
    }

    public function setSkills($value)
    {
        $this->skills = $value;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($value)
    {
        $this->phone = $value;
    }

    public function getLinkedin_url()
    {
        return $this->linkedin_url;
    }

    public function setLinkedin_url($value)
    {
        $this->linkedin_url = $value;
    }

    public function getPortfolio_url()
    {
        return $this->portfolio_url;
    }

    public function setPortfolio_url($value)
    {
        $this->portfolio_url = $value;
    }

    #[ORM\OneToMany(mappedBy: "interviewee_id", targetEntity: Interviews::class)]
    private Collection $interviewss;

        public function getInterviewss(): Collection
        {
            return $this->interviewss;
        }
    
        public function addInterviews(Interviews $interviews): self
        {
            if (!$this->interviewss->contains($interviews)) {
                $this->interviewss[] = $interviews;
                $interviews->setInterviewee_id($this);
            }
    
            return $this;
        }
    
        public function removeInterviews(Interviews $interviews): self
        {
            if ($this->interviewss->removeElement($interviews)) {
                // set the owning side to null (unless already changed)
                if ($interviews->getInterviewee_id() === $this) {
                    $interviews->setInterviewee_id(null);
                }
            }
    
            return $this;
        }
}
