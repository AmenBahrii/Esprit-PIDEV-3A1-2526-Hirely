<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Joboffer;
use Doctrine\Common\Collections\Collection;
use App\Entity\Interviews;

#[ORM\Entity]
class Application
{

    #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: "applicationId", type: "integer")]
private ?int $applicationId = null;

    #[ORM\Column(name: "applicationDate", type: "date")]
private \DateTimeInterface $applicationDate;

    #[ORM\Column(name: "coverLetter", type: "text")]
private string $coverLetter;

#[ORM\Column(name: "currentStatus", type: "string", length: 50)]
private string $currentStatus;

#[ORM\Column(name: "resumePath", type: "string", length: 255)]
private string $resumePath;

#[ORM\Column(name: "lastUpdateDate", type: "datetime")]
private \DateTimeInterface $lastUpdateDate;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "applications")]
#[ORM\JoinColumn(name: "user_id", referencedColumnName: "user_id", onDelete: "CASCADE")]
private ?Users $user = null;

        #[ORM\ManyToOne(targetEntity: Joboffer::class, inversedBy: "applications")]
#[ORM\JoinColumn(name: "jobOfferId", referencedColumnName: "jobOfferId")]
private ?Joboffer $jobOffer = null;

    #[ORM\Column(name: "expectedSalary", type: "float")]
private float $expectedSalary;

#[ORM\Column(name: "availabilityDate", type: "date")]
private \DateTimeInterface $availabilityDate;

#[ORM\Column(name: "phone", type: "string", length: 50)]
private string $phone;

#[ORM\Column(name: "email", type: "string", length: 255)]
private string $email;

#[ORM\Column(name: "experienceYears", type: "integer")]
private int $experienceYears;

#[ORM\Column(name: "portfolioUrl", type: "string", length: 255)]
private string $portfolioUrl;

#[ORM\Column(name: "score", type: "float", nullable: true)]
private ?float $score = null;

#[ORM\Column(name: "reviewNote", type: "text", nullable: true)]
private ?string $reviewNote = null;

    public function getApplicationId()
    {
        return $this->applicationId;
    }

    public function setApplicationId($value)
    {
        $this->applicationId = $value;
    }

    public function getApplicationDate()
    {
        return $this->applicationDate;
    }

    public function setApplicationDate($value)
    {
        $this->applicationDate = $value;
    }

    public function getCoverLetter()
    {
        return $this->coverLetter;
    }

    public function setCoverLetter($value)
    {
        $this->coverLetter = $value;
    }

    public function getCurrentStatus()
    {
        return $this->currentStatus;
    }

    public function setCurrentStatus($value)
    {
        $this->currentStatus = $value;
    }

    public function getResumePath()
    {
        return $this->resumePath;
    }

    public function setResumePath($value)
    {
        $this->resumePath = $value;
    }

    public function getLastUpdateDate()
    {
        return $this->lastUpdateDate;
    }

    public function setLastUpdateDate($value)
    {
        $this->lastUpdateDate = $value;
    }

    public function getUser(): ?Users
{
    return $this->user;
}

public function setUser(?Users $user): self
{
    $this->user = $user;
    return $this;
}

    public function getJobOffer(): ?Joboffer
{
    return $this->jobOffer;
}

public function setJobOffer(?Joboffer $jobOffer): self
{
    $this->jobOffer = $jobOffer;
    return $this;
}

    public function getExpectedSalary()
    {
        return $this->expectedSalary;
    }

    public function setExpectedSalary($value)
    {
        $this->expectedSalary = $value;
    }

    public function getAvailabilityDate()
    {
        return $this->availabilityDate;
    }

    public function setAvailabilityDate($value)
    {
        $this->availabilityDate = $value;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($value)
    {
        $this->phone = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getExperienceYears()
    {
        return $this->experienceYears;
    }

    public function setExperienceYears($value)
    {
        $this->experienceYears = $value;
    }

    public function getPortfolioUrl()
    {
        return $this->portfolioUrl;
    }

    public function setPortfolioUrl($value)
    {
        $this->portfolioUrl = $value;
    }

    public function getScore(): ?float
{
    return $this->score;
}

    public function setScore($value)
    {
        $this->score = $value;
    }

    public function getReviewNote(): ?string
{
    return $this->reviewNote;
}

    public function setReviewNote($value)
    {
        $this->reviewNote = $value;
    }

    #[ORM\OneToMany(mappedBy: "application_id", targetEntity: Interviews::class)]
    private Collection $interviewss;

        public function getInterviewss(): Collection
        {
            return $this->interviewss;
        }
    
        public function addInterviews(Interviews $interviews): self
        {
            if (!$this->interviewss->contains($interviews)) {
                $this->interviewss[] = $interviews;
                $interviews->setApplication_id($this);
            }
    
            return $this;
        }
    
        public function removeInterviews(Interviews $interviews): self
        {
            if ($this->interviewss->removeElement($interviews)) {
                // set the owning side to null (unless already changed)
                if ($interviews->getApplication_id() === $this) {
                    $interviews->setApplication_id(null);
                }
            }
    
            return $this;
        }
}
