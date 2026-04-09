<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\InterviewRepository;

#[ORM\Entity(repositoryClass: InterviewRepository::class)]
#[ORM\Table(name: 'interviews')]
class Interview
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $interview_id = null;

    public function getInterview_id(): ?int
    {
        return $this->interview_id;
    }

    public function setInterview_id(int $interview_id): self
    {
        $this->interview_id = $interview_id;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'applicationId')]
    private ?Application $application = null;

    public function getApplication(): ?Application
    {
        return $this->application;
    }

    public function setApplication(?Application $application): self
    {
        $this->application = $application;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(name: 'interviewer_id', referencedColumnName: 'user_id')]
    private ?User $user = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    #[ORM\ManyToOne(targetEntity: InterviewType::class, inversedBy: 'interviews')]
    #[ORM\JoinColumn(name: 'type_id', referencedColumnName: 'type_id')]
    private ?InterviewType $interviewType = null;

    public function getInterviewType(): ?InterviewType
    {
        return $this->interviewType;
    }

    public function setInterviewType(?InterviewType $interviewType): self
    {
        $this->interviewType = $interviewType;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $scheduled_at = null;

    public function getScheduled_at(): ?\DateTimeInterface
    {
        return $this->scheduled_at;
    }

    public function setScheduled_at(\DateTimeInterface $scheduled_at): self
    {
        $this->scheduled_at = $scheduled_at;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $location_url = null;

    public function getLocation_url(): ?string
    {
        return $this->location_url;
    }

    public function setLocation_url(?string $location_url): self
    {
        $this->location_url = $location_url;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $status = null;

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $meeting_notes = null;

    public function getMeeting_notes(): ?string
    {
        return $this->meeting_notes;
    }

    public function setMeeting_notes(?string $meeting_notes): self
    {
        $this->meeting_notes = $meeting_notes;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTimeInterface $created_at = null;

    public function getCreated_at(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreated_at(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: InterviewEvaluation::class, mappedBy: 'interview')]
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
