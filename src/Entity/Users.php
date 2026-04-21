<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Role;
use Doctrine\Common\Collections\Collection;
use App\Entity\Recruiter_profiles;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity]
#[ORM\Table(name: "users")]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['google_id'], message: 'This Google account is already linked to another user.', ignoreNull: true)]
class Users implements UserInterface, PasswordAuthenticatedUserInterface
{

  #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: "user_id", type: "integer")]
private ?int $id = null;

#[ORM\Column(name: "first_name", type: "string", length: 100)]
#[Assert\NotBlank(message: "First name is required")]
#[Assert\Length(min: 2, max: 100)]
private string $first_name;

#[ORM\Column(name: "last_name", type: "string", length: 100)]
#[Assert\NotBlank(message: "Last name is required")]
#[Assert\Length(min: 2, max: 100)]
private string $last_name;

#[ORM\Column(name: "email", type: "string", length: 255)]
#[Assert\NotBlank(message: "Email is required")]
#[Assert\Email(message: "Invalid email format")]
private string $email;

#[ORM\Column(name: "password", type: "string", length: 255)]
private string $password;

#[ORM\ManyToOne(targetEntity: Role::class, inversedBy: "userss")]
#[ORM\JoinColumn(name: "role_id", referencedColumnName: "role_id", onDelete: "CASCADE")]
#[Assert\NotNull(message: "Role is required")]
private ?Role $role = null;

#[ORM\Column(name: "status", type: "string", length: 20)]
#[Assert\Choice(choices: ["active", "inactive"], message: "Invalid status")]
private string $status;

#[ORM\Column(name: "profile_pic", type: "string", length: 255, nullable: true)]
#[Assert\Url(message: "Profile picture must be a valid URL")]
private ?string $profile_pic = null;

#[ORM\Column(name: "face_data", type: "text", nullable: true)]
private ?string $face_data = null;

#[ORM\Column(name: "google_id", type: "string", length: 255, nullable: true)]
private ?string $google_id = null;
    public function getId(): ?int
{
    return $this->id;
}

public function setId(int $id): self
{
    $this->id = $id;
    return $this;
}

public function getUserIdentifier(): string
{
    return $this->email ?? '';
}

public function getRoles(): array
{
    if (!$this->role) {
        return ['ROLE_USER'];
    }

    return ['ROLE_' . strtoupper($this->role->getName())];
}

public function getRole(): ?Role
{
    return $this->role;
}



public function getPassword(): ?string
{
    return $this->password;
}

public function eraseCredentials(): void
{
    // nothing needed for now
}

    public function getFirstName()
    {
        return $this->first_name;
    }

    public function setFirstName($value)
    {
        $this->first_name = $value;
    }

    public function getLastName()
    {
        return $this->last_name;
    }

    public function setLastName($value)
    {
        $this->last_name = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function setPassword($value)
    {
        $this->password = $value;
    }

   

    public function setRole(?Role $role): self
{
    $this->role = $role;
    return $this;
}

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getProfilePic()
    {
        return $this->profile_pic;
    }

    public function setProfilePic($value)
    {
        $this->profile_pic = $value;
    }

    public function getFaceData()
    {
        return $this->face_data;
    }

    public function setFaceData($value)
    {
        $this->face_data = $value;
    }

    public function getGoogleId()
    {
        return $this->google_id;
    }

    public function setGoogleId($value)
    {
        $this->google_id = $value;
    }

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Interviewee_profiles::class)]
    private Collection $interviewee_profiless;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Joboffer::class)]
    private Collection $joboffers;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Password_reset_otp::class)]
    private Collection $password_reset_otps;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Recruiter_profiles::class)]
    private Collection $recruiter_profiless;

    #[ORM\OneToMany(mappedBy: "user", targetEntity: Application::class)]
    private Collection $applications;

    #[ORM\OneToMany(mappedBy: "recruiter_id", targetEntity: Interview_evaluations::class)]
    private Collection $interview_evaluationss;

        public function getInterview_evaluationss(): Collection
        {
            return $this->interview_evaluationss;
        }
    
        public function addInterview_evaluations(Interview_evaluations $interview_evaluations): self
        {
            if (!$this->interview_evaluationss->contains($interview_evaluations)) {
                $this->interview_evaluationss[] = $interview_evaluations;
                $interview_evaluations->setRecruiter_id($this);
            }
    
            return $this;
        }
    
        public function removeInterview_evaluations(Interview_evaluations $interview_evaluations): self
        {
            if ($this->interview_evaluationss->removeElement($interview_evaluations)) {
                // set the owning side to null (unless already changed)
                if ($interview_evaluations->getRecruiter_id() === $this) {
                    $interview_evaluations->setRecruiter_id(null);
                }
            }
    
            return $this;
        }
        
    public function __construct()
{
    $this->joboffers = new ArrayCollection();
    $this->applications = new ArrayCollection();
    $this->notificationss = new ArrayCollection();
}
    #[ORM\OneToMany(mappedBy: "user", targetEntity: Notifications::class)]
    private Collection $notificationss;

    #[ORM\OneToMany(mappedBy: "recruiter", targetEntity: Interviews::class)]
    private Collection $interviewss;
}
