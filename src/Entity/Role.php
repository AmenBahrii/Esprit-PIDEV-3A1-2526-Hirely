<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Users;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Role
{

    #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: "role_id", type: "integer")]
private ?int $id = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: "Role name is required")]
    #[Assert\Length(min: 3, max: 50, minMessage: "Role name must be at least {{ limit }} characters")]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    #[Assert\NotBlank(message: "Description is required")]
    #[Assert\Length(min: 5, max: 255, minMessage: "Description must be at least {{ limit }} characters")]
    private string $description;

    #[ORM\Column(type: "string", length: 20)]
    #[Assert\NotBlank(message: "Status is required")]
    #[Assert\Choice(choices: ["active", "inactive"], message: "Please choose a valid status")]
    private string $status;

    #[ORM\Column(type: "string", length: 50, nullable: true)]
#[Assert\NotBlank(message: "Default dashboard is required")]
private ?string $default_dashboard = null;

    public function getid()
    {
        return $this->id;
    }

    public function setid($value)
    {
        $this->id = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($value)
    {
        $this->name = $value;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($value)
    {
        $this->description = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

public function getDefaultDashboard(): ?string
{
    return $this->default_dashboard;
}
    public function setDefaultDashboard($value)
    {
        $this->default_dashboard = $value;
    }

    /**
     * @var Collection<int, Users>
     */
    #[ORM\OneToMany(mappedBy: "role", targetEntity: Users::class)]
    private Collection $userss;

        public function getUserss(): Collection
        {
            return $this->userss;
        }
    
        public function addUsers(Users $users): self
        {
            if (!$this->userss->contains($users)) {
                $this->userss[] = $users;
                $users->setRole($this);
            }
    
            return $this;
        }
    
        public function removeUsers(Users $users): self
        {
            if ($this->userss->removeElement($users)) {
                // set the owning side to null (unless already changed)
                if ($users->getRole() === $this) {
                    $users->setRole(null);
                }
            }
    
            return $this;
        }
}
