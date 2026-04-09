<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\InterviewTypeRepository;

#[ORM\Entity(repositoryClass: InterviewTypeRepository::class)]
#[ORM\Table(name: 'interview_types')]
class InterviewType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $type_id = null;

    public function getType_id(): ?int
    {
        return $this->type_id;
    }

    public function setType_id(int $type_id): self
    {
        $this->type_id = $type_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $type_name = null;

    public function getType_name(): ?string
    {
        return $this->type_name;
    }

    public function setType_name(string $type_name): self
    {
        $this->type_name = $type_name;
        return $this;
    }

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $default_duration = null;

    public function getDefault_duration(): ?int
    {
        return $this->default_duration;
    }

    public function setDefault_duration(?int $default_duration): self
    {
        $this->default_duration = $default_duration;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'interviewType')]
    private Collection $interviews;

    /**
     * @return Collection<int, Interview>
     */
    public function getInterviews(): Collection
    {
        if (!$this->interviews instanceof Collection) {
            $this->interviews = new ArrayCollection();
        }
        return $this->interviews;
    }

    public function addInterview(Interview $interview): self
    {
        if (!$this->getInterviews()->contains($interview)) {
            $this->getInterviews()->add($interview);
        }
        return $this;
    }

    public function removeInterview(Interview $interview): self
    {
        $this->getInterviews()->removeElement($interview);
        return $this;
    }

}
