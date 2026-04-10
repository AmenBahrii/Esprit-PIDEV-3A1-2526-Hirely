<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;
use Doctrine\Common\Collections\Collection;
use App\Entity\Application;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity]
class Joboffer
{

        #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: "jobOfferId", type: "integer")]
private ?int $id = null;

    #[ORM\Column(name: "title", type: "string", length: 255)]
private string $title;

#[ORM\Column(name: "description", type: "text")]
private string $description;

#[ORM\Column(name: "contractType", type: "string")]
private string $contractType;

#[ORM\Column(name: "salary", type: "float")]
private float $salary;

#[ORM\Column(name: "location", type: "string", length: 255)]
private string $location;

#[ORM\Column(name: "experienceRequired", type: "integer")]
private int $experienceRequired;

#[ORM\Column(name: "publicationDate", type: "date")]
private \DateTimeInterface $publicationDate;

#[ORM\Column(name: "status", type: "string")]
private string $status;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "joboffers")]
#[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
private ?Users $user = null;

public function getUser(): ?Users
{
    return $this->user;
}
public function __construct()
{
    $this->applications = new ArrayCollection();
}
public function setUser(?Users $user): self
{
    $this->user = $user;
    return $this;
}

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getContractType()
    {
        return $this->contractType;
    }

    public function setContractType($value)
    {
        $this->contractType = $value;
    }

    public function getSalary()
    {
        return $this->salary;
    }

    public function setSalary($value)
    {
        $this->salary = $value;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($value)
    {
        $this->location = $value;
    }

    public function getExperienceRequired()
    {
        return $this->experienceRequired;
    }

    public function setExperienceRequired($value)
    {
        $this->experienceRequired = $value;
    }

    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    public function setPublicationDate($value)
    {
        $this->publicationDate = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    

    #[ORM\OneToMany(mappedBy: 'jobOffer', targetEntity: Application::class)]
private Collection $applications;

        public function getApplications(): Collection
        {
            return $this->applications;
        }
    
        public function addApplication(Application $application): self
        {
            if (!$this->applications->contains($application)) {
                $this->applications[] = $application;
                $application->setJobOffer($this);
            }
    
            return $this;
        }
    
        public function removeApplication(Application $application): self
        {
            if ($this->applications->removeElement($application)) {
                // set the owning side to null (unless already changed)
                if ($application->getJobOffer() === $this) {
                    $application->setJobOffer(null);
                }
            }
    
            return $this;
        }   
}
