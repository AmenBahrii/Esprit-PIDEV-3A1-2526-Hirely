<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
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
    private ?string $email = null;

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $password = null;

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

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
    private ?string $face_data = null;

    public function getFace_data(): ?string
    {
        return $this->face_data;
    }

    public function setFace_data(?string $face_data): self
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

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $is_verified = null;

    public function is_verified(): ?bool
    {
        return $this->is_verified;
    }

    public function setIs_verified(?bool $is_verified): self
    {
        $this->is_verified = $is_verified;
        return $this;
    }

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last_login = null;

    public function getLast_login(): ?\DateTimeInterface
    {
        return $this->last_login;
    }

    public function setLast_login(?\DateTimeInterface $last_login): self
    {
        $this->last_login = $last_login;
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

    #[ORM\OneToMany(targetEntity: Interview::class, mappedBy: 'user')]
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

}
