<?php

namespace App\Entity;

<<<<<<< HEAD
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
=======
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email.')]
>>>>>>> OnboardingCoordination
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
<<<<<<< HEAD
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

=======
    private ?int $user_id = null;

    public function getUser_id(): ?int
    {
        return $this->user_id;
    }

    public function setUser_id(int $user_id): self
    {
        $this->user_id = $user_id;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $first_name = null;

    public function getFirst_name(): ?string
    {
        return $this->first_name;
    }

    public function setFirst_name(?string $first_name): self
    {
        $this->first_name = $first_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $last_name = null;

    public function getLast_name(): ?string
    {
        return $this->last_name;
    }

    public function setLast_name(?string $last_name): self
    {
        $this->last_name = $last_name;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    #[Assert\Email(message: 'Please provide a valid email address.')]
    private ?string $email = null;

>>>>>>> OnboardingCoordination
    public function getEmail(): ?string
    {
        return $this->email;
    }

<<<<<<< HEAD
    public function setEmail(string $email): self
=======
    public function setEmail(?string $email): self
>>>>>>> OnboardingCoordination
    {
        $this->email = $email;
        return $this;
    }

<<<<<<< HEAD
=======
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

>>>>>>> OnboardingCoordination
    public function getPassword(): ?string
    {
        return $this->password;
    }

<<<<<<< HEAD
    public function setPassword(string $password): self
=======
    public function setPassword(?string $password): self
>>>>>>> OnboardingCoordination
    {
        $this->password = $password;
        return $this;
    }

<<<<<<< HEAD
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
=======
    public function getUserIdentifier(): string
    {
        return (string) ($this->email ?? '');
>>>>>>> OnboardingCoordination
    }

    public function getRoles(): array
    {
<<<<<<< HEAD
        return [$this->role];
=======
        $roleName = strtoupper(trim((string) $this->getRole()?->getName()));
        $roles = ['ROLE_USER'];

        if ('' === $roleName) {
            return $roles;
        }

        if (!str_starts_with($roleName, 'ROLE_')) {
            $roleName = 'ROLE_' . $roleName;
        }

        $roles[] = $roleName;

        return array_values(array_unique($roles));
>>>>>>> OnboardingCoordination
    }

    public function eraseCredentials(): void
    {
    }

<<<<<<< HEAD
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
=======
    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'role_id')]
    private ?Role $role = null;

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;
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

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $profile_pic = null;

    public function getProfile_pic(): ?string
    {
        return $this->profile_pic;
    }

    public function setProfile_pic(?string $profile_pic): self
    {
        $this->profile_pic = $profile_pic;
        return $this;
    }

    #[ORM\Column(type: 'blob', nullable: true)]
    private $face_data = null;

    public function getFaceData()
    {
        return $this->face_data;
    }

    public function setFaceData($face_data): self
    {
        $this->face_data = $face_data;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $google_id = null;

    public function getGoogle_id(): ?string
    {
        return $this->google_id;
    }

    public function setGoogle_id(?string $google_id): self
    {
        $this->google_id = $google_id;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Application::class, mappedBy: 'user')]
    private Collection $applications;

    /**
     * @return Collection<int, Application>
     */
    public function getApplications(): Collection
    {
        if (!$this->applications instanceof Collection) {
            $this->applications = new ArrayCollection();
        }
        return $this->applications;
    }

    public function addApplication(Application $application): self
    {
        if (!$this->getApplications()->contains($application)) {
            $this->getApplications()->add($application);
        }
        return $this;
    }

    public function removeApplication(Application $application): self
    {
        $this->getApplications()->removeElement($application);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumComment::class, mappedBy: 'user')]
    private Collection $forumComments;

    /**
     * @return Collection<int, ForumComment>
     */
    public function getForumComments(): Collection
    {
        if (!$this->forumComments instanceof Collection) {
            $this->forumComments = new ArrayCollection();
        }
        return $this->forumComments;
    }

    public function addForumComment(ForumComment $forumComment): self
    {
        if (!$this->getForumComments()->contains($forumComment)) {
            $this->getForumComments()->add($forumComment);
        }
        return $this;
    }

    public function removeForumComment(ForumComment $forumComment): self
    {
        $this->getForumComments()->removeElement($forumComment);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumComment::class, mappedBy: 'editedByUser')]
    private Collection $editedForumComments;

    /**
     * @return Collection<int, ForumComment>
     */
    public function getEditedForumComments(): Collection
    {
        if (!$this->editedForumComments instanceof Collection) {
            $this->editedForumComments = new ArrayCollection();
        }
        return $this->editedForumComments;
    }

    public function addEditedForumComment(ForumComment $editedForumComment): self
    {
        if (!$this->getEditedForumComments()->contains($editedForumComment)) {
            $this->getEditedForumComments()->add($editedForumComment);
        }
        return $this;
    }

    public function removeEditedForumComment(ForumComment $editedForumComment): self
    {
        $this->getEditedForumComments()->removeElement($editedForumComment);
        return $this;
    }

    #[ORM\OneToOne(targetEntity: ForumInteraction::class, mappedBy: 'user')]
    private ?ForumInteraction $forumInteraction = null;

    public function getForumInteraction(): ?ForumInteraction
    {
        return $this->forumInteraction;
    }

    public function setForumInteraction(?ForumInteraction $forumInteraction): self
    {
        $this->forumInteraction = $forumInteraction;
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumNotification::class, mappedBy: 'user')]
    private Collection $forumNotifications;

    /**
     * @return Collection<int, ForumNotification>
     */
    public function getForumNotifications(): Collection
    {
        if (!$this->forumNotifications instanceof Collection) {
            $this->forumNotifications = new ArrayCollection();
        }
        return $this->forumNotifications;
    }

    public function addForumNotification(ForumNotification $forumNotification): self
    {
        if (!$this->getForumNotifications()->contains($forumNotification)) {
            $this->getForumNotifications()->add($forumNotification);
        }
        return $this;
    }

    public function removeForumNotification(ForumNotification $forumNotification): self
    {
        $this->getForumNotifications()->removeElement($forumNotification);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumNotification::class, mappedBy: 'actorUser')]
    private Collection $actorForumNotifications;

    /**
     * @return Collection<int, ForumNotification>
     */
    public function getActorForumNotifications(): Collection
    {
        if (!$this->actorForumNotifications instanceof Collection) {
            $this->actorForumNotifications = new ArrayCollection();
        }
        return $this->actorForumNotifications;
    }

    public function addActorForumNotification(ForumNotification $actorForumNotification): self
    {
        if (!$this->getActorForumNotifications()->contains($actorForumNotification)) {
            $this->getActorForumNotifications()->add($actorForumNotification);
        }
        return $this;
    }

    public function removeActorForumNotification(ForumNotification $actorForumNotification): self
    {
        $this->getActorForumNotifications()->removeElement($actorForumNotification);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumPost::class, mappedBy: 'user')]
    private Collection $forumPosts;

    /**
     * @return Collection<int, ForumPost>
     */
    public function getForumPosts(): Collection
    {
        if (!$this->forumPosts instanceof Collection) {
            $this->forumPosts = new ArrayCollection();
        }
        return $this->forumPosts;
    }

    public function addForumPost(ForumPost $forumPost): self
    {
        if (!$this->getForumPosts()->contains($forumPost)) {
            $this->getForumPosts()->add($forumPost);
        }
        return $this;
    }

    public function removeForumPost(ForumPost $forumPost): self
    {
        $this->getForumPosts()->removeElement($forumPost);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: ForumPost::class, mappedBy: 'editedByUser')]
    private Collection $editedForumPosts;

    /**
     * @return Collection<int, ForumPost>
     */
    public function getEditedForumPosts(): Collection
    {
        if (!$this->editedForumPosts instanceof Collection) {
            $this->editedForumPosts = new ArrayCollection();
        }
        return $this->editedForumPosts;
    }

    public function addEditedForumPost(ForumPost $editedForumPost): self
    {
        if (!$this->getEditedForumPosts()->contains($editedForumPost)) {
            $this->getEditedForumPosts()->add($editedForumPost);
        }
        return $this;
    }

    public function removeEditedForumPost(ForumPost $editedForumPost): self
    {
        $this->getEditedForumPosts()->removeElement($editedForumPost);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: Joboffer::class, mappedBy: 'user')]
    private Collection $joboffers;

    /**
     * @return Collection<int, Joboffer>
     */
    public function getJoboffers(): Collection
    {
        if (!$this->joboffers instanceof Collection) {
            $this->joboffers = new ArrayCollection();
        }
        return $this->joboffers;
    }

    public function addJoboffer(Joboffer $joboffer): self
    {
        if (!$this->getJoboffers()->contains($joboffer)) {
            $this->getJoboffers()->add($joboffer);
        }
        return $this;
    }

    public function removeJoboffer(Joboffer $joboffer): self
    {
        $this->getJoboffers()->removeElement($joboffer);
        return $this;
    }

    #[ORM\OneToMany(targetEntity: PasswordResetOtp::class, mappedBy: 'user')]
    private Collection $passwordResetOtps;

     public function __construct()
     {
         $this->applications = new ArrayCollection();
         $this->forumComments = new ArrayCollection();
         $this->editedForumComments = new ArrayCollection();
        $this->forumNotifications = new ArrayCollection();
        $this->actorForumNotifications = new ArrayCollection();
        $this->forumPosts = new ArrayCollection();
         $this->editedForumPosts = new ArrayCollection();
         $this->joboffers = new ArrayCollection();
         $this->passwordResetOtps = new ArrayCollection();
         $this->onboardingplans = new ArrayCollection();
     }

    /**
     * @return Collection<int, PasswordResetOtp>
     */
    public function getPasswordResetOtps(): Collection
    {
        if (!$this->passwordResetOtps instanceof Collection) {
            $this->passwordResetOtps = new ArrayCollection();
        }
        return $this->passwordResetOtps;
    }

    public function addPasswordResetOtp(PasswordResetOtp $passwordResetOtp): self
    {
        if (!$this->getPasswordResetOtps()->contains($passwordResetOtp)) {
            $this->getPasswordResetOtps()->add($passwordResetOtp);
        }
        return $this;
    }

    public function removePasswordResetOtp(PasswordResetOtp $passwordResetOtp): self
    {
        $this->getPasswordResetOtps()->removeElement($passwordResetOtp);
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function getId(): ?int
    {
        return $this->user_id;
    }

    public function setId(int $id): static
    {
        $this->user_id = $id;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(?string $first_name): static
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(?string $last_name): static
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getProfilePic(): ?string
    {
        return $this->profile_pic;
    }

    public function setProfilePic(?string $profile_pic): static
    {
        $this->profile_pic = $profile_pic;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->google_id;
    }

    public function setGoogleId(?string $google_id): static
    {
        $this->google_id = $google_id;

        return $this;
    }

    #[ORM\OneToMany(targetEntity: Onboardingplan::class, mappedBy: 'user')]
    private Collection $onboardingplans;

    public function getOnboardingplans(): Collection
    {
        if (!$this->onboardingplans instanceof Collection) {
            $this->onboardingplans = new ArrayCollection();
        }
        return $this->onboardingplans;
    }

    public function addOnboardingplan(Onboardingplan $onboardingplan): self
    {
        if (!$this->getOnboardingplans()->contains($onboardingplan)) {
            $this->getOnboardingplans()->add($onboardingplan);
        }
        return $this;
    }

    public function removeOnboardingplan(Onboardingplan $onboardingplan): self
    {
        $this->getOnboardingplans()->removeElement($onboardingplan);
        return $this;
>>>>>>> OnboardingCoordination
    }
}
