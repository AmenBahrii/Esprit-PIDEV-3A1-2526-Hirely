<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'user_id', type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'first_name', length: 100, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'role_id', nullable: true)]
    private ?Role $role = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $status = 'active';

    #[ORM\Column(name: 'profile_pic', length: 255, nullable: true)]
    private ?string $profilePic = null;

    #[ORM\Column(name: 'face_data', type: Types::BLOB, nullable: true)]
    private mixed $faceData = null;

    #[ORM\Column(name: 'google_id', length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'is_verified', type: Types::BOOLEAN, nullable: true)]
    private ?bool $isVerified = false;

    #[ORM\Column(name: 'last_login', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLogin = null;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: ForumPost::class)]
    private Collection $authoredPosts;

    #[ORM\OneToMany(mappedBy: 'editedBy', targetEntity: ForumPost::class)]
    private Collection $editedPosts;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: ForumComment::class)]
    private Collection $authoredComments;

    #[ORM\OneToMany(mappedBy: 'editedBy', targetEntity: ForumComment::class)]
    private Collection $editedComments;

    public function __construct()
    {
        $this->authoredPosts = new ArrayCollection();
        $this->editedPosts = new ArrayCollection();
        $this->authoredComments = new ArrayCollection();
        $this->editedComments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getRoleEntity(): ?Role
    {
        return $this->role;
    }

    public function setRole(?Role $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getProfilePic(): ?string
    {
        return $this->profilePic;
    }

    public function setProfilePic(?string $profilePic): self
    {
        $this->profilePic = $profilePic;

        return $this;
    }

    public function getFaceData(): mixed
    {
        return $this->faceData;
    }

    public function setFaceData(mixed $faceData): self
    {
        $this->faceData = $faceData;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;

        return $this;
    }

    public function isVerified(): bool
    {
        return (bool) $this->isVerified;
    }

    public function setIsVerified(?bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        $roleName = strtolower((string) ($this->role?->getName() ?? ''));

        if ($roleName === 'admin' || $roleName === 'administrator') {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }

    public function isActive(): bool
    {
        return strtolower((string) ($this->status ?? 'active')) !== 'inactive';
    }

    public function getDisplayName(): string
    {
        $name = trim(sprintf('%s %s', $this->firstName ?? '', $this->lastName ?? ''));

        return $name !== '' ? $name : ($this->email ?? 'User');
    }
}
