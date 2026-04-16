<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Joboffer;
use Doctrine\Common\Collections\Collection;
use App\Entity\Interviews;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[UniqueEntity(fields: ['user', 'jobOffer'], message: "Cannot apply to the same offer twice.")]
class Application
{

    #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: "applicationId", type: "integer")]
private ?int $applicationId = null;

    #[ORM\Column(name: "applicationDate", type: "date")]
#[Assert\NotNull(message: "Application date is required")]
private \DateTimeInterface $applicationDate;

    #[ORM\Column(name: "coverLetter", type: "text")]
#[Assert\NotBlank(message: "Cover letter is required")]
#[Assert\Length(min: 10, minMessage: "Cover letter must be at least {{ limit }} characters")]
private string $coverLetter;

#[ORM\Column(name: "currentStatus", type: "string", length: 50)]
#[Assert\NotBlank(message: "Status is required")]
#[Assert\Choice(choices: ["pending", "Accepted", "Rejected"], message: "Please choose a valid status")]
private string $currentStatus;

#[ORM\Column(name: "resumePath", type: "string", length: 255)]
#[Assert\NotBlank(message: "Resume is required")]
#[Assert\Length(max: 255)]
private string $resumePath;

#[ORM\Column(name: "lastUpdateDate", type: "datetime")]
#[Assert\NotNull(message: "Last update date is required")]
private \DateTimeInterface $lastUpdateDate;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "applications")]
#[ORM\JoinColumn(name: "user_id", referencedColumnName: "user_id", onDelete: "CASCADE")]
#[Assert\NotNull(message: "A candidate is required for the application")]
private ?Users $user = null;

        #[ORM\ManyToOne(targetEntity: Joboffer::class, inversedBy: "applications")]
#[ORM\JoinColumn(name: "jobOfferId", referencedColumnName: "jobOfferId")]
#[Assert\NotNull(message: "A job offer is required for the application")]
private ?Joboffer $jobOffer = null;

    #[ORM\Column(name: "expectedSalary", type: "float")]
#[Assert\NotBlank(message: "Expected salary is required")]
#[Assert\PositiveOrZero(message: "Expected salary must be positive")]
private float $expectedSalary;

#[ORM\Column(name: "availabilityDate", type: "date")]
#[Assert\NotBlank(message: "Availability date is required")]
#[Assert\GreaterThanOrEqual("today", message: "Date cannot be in the past")]
private ?\DateTimeInterface $availabilityDate = null;

#[ORM\Column(name: "phone", type: "string", length: 50)]
#[Assert\NotBlank(message: "Phone is required")]
#[Assert\Length(min: 8, max: 50, minMessage: "Phone must contain at least {{ limit }} characters")]
#[Assert\Regex(pattern: "/^[0-9+()\\- ]+$/", message: "Phone number contains invalid characters")]
private string $phone;

#[ORM\Column(name: "email", type: "string", length: 255)]
#[Assert\NotBlank(message: "Email is required")]
#[Assert\Email(message: "Invalid email format")]
private string $email;

#[ORM\Column(name: "experienceYears", type: "integer")]
#[Assert\NotBlank(message: "Experience is required")]
#[Assert\PositiveOrZero(message: "Experience cannot be negative")]
private int $experienceYears;

#[ORM\Column(name: "portfolioUrl", type: "string", length: 255)]
#[Assert\NotBlank(message: "Portfolio URL is required")]
#[Assert\Url(message: "Portfolio must be a valid URL")]
private string $portfolioUrl;

#[ORM\Column(name: "score", type: "float", nullable: true)]
#[Assert\PositiveOrZero(message: "Score cannot be negative")]
#[Assert\LessThanOrEqual(value: 100, message: "Score cannot be greater than 100")]
private ?float $score = null;

#[ORM\Column(name: "reviewNote", type: "text", nullable: true)]
#[Assert\Length(max: 5000, maxMessage: "Review note cannot exceed {{ limit }} characters")]
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

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        if ($this->availabilityDate !== null && $this->applicationDate instanceof \DateTimeInterface) {
            if ($this->availabilityDate < $this->applicationDate) {
                $context->buildViolation('Availability date cannot be before the application date.')
                    ->atPath('availabilityDate')
                    ->addViolation();
            }
        }

        if ($this->availabilityDate !== null && $this->jobOffer?->getPublicationDate() instanceof \DateTimeInterface) {
            if ($this->availabilityDate < $this->jobOffer->getPublicationDate()) {
                $context->buildViolation('Availability date cannot be before the publication date of the selected job offer.')
                    ->atPath('availabilityDate')
                    ->addViolation();
            }
        }

        if ($this->jobOffer instanceof Joboffer) {
            if (strcasecmp($this->jobOffer->getStatus(), 'Open') !== 0) {
                $context->buildViolation('You can only apply to open job offers.')
                    ->atPath('jobOffer')
                    ->addViolation();
            }

            if (isset($this->experienceYears) && $this->experienceYears < $this->jobOffer->getExperienceRequired()) {
                $context->buildViolation('Your experience must meet or exceed the experience required for this job offer.')
                    ->atPath('experienceYears')
                    ->addViolation();
            }
        }

        if ($this->user instanceof Users && $this->email !== null && strcasecmp($this->email, $this->user->getEmail()) !== 0) {
            $context->buildViolation('Application email must match the email of the logged-in candidate.')
                ->atPath('email')
                ->addViolation();
        }
    }
}
