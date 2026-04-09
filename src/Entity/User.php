<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $role = 'ROLE_INTERVIEWEE';

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToOne(targetEntity: RecruiterProfile::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?RecruiterProfile $recruiterProfile = null;

    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'recruiter')]
    private Collection $interviews;

    #[ORM\OneToMany(targetEntity: InterviewEvaluation::class, mappedBy: 'recruiter')]
    private Collection $evaluations;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user')]
    private Collection $notifications;

    public function __construct()
    {
        $this->interviews = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->notifications = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getRecruiterProfile(): ?RecruiterProfile
    {
        return $this->recruiterProfile;
    }

    public function setRecruiterProfile(?RecruiterProfile $recruiterProfile): self
    {
        if ($recruiterProfile === null && $this->recruiterProfile !== null) {
            $this->recruiterProfile->setUser(null);
        }
        if ($recruiterProfile !== null && $recruiterProfile->getUser() !== $this) {
            $recruiterProfile->setUser($this);
        }
        $this->recruiterProfile = $recruiterProfile;
        return $this;
    }

    public function getInterviews(): Collection
    {
        return $this->interviews;
    }

    public function addInterview(Interview $interview): self
    {
        if (!$this->interviews->contains($interview)) {
            $this->interviews->add($interview);
            $interview->setRecruiter($this);
        }
        return $this;
    }

    public function removeInterview(Interview $interview): self
    {
        if ($this->interviews->removeElement($interview)) {
            if ($interview->getRecruiter() === $this) {
                $interview->setRecruiter(null);
            }
        }
        return $this;
    }

    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(InterviewEvaluation $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setRecruiter($this);
        }
        return $this;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }

    public function getRoles(): array
    {
        return [$this->role];
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
