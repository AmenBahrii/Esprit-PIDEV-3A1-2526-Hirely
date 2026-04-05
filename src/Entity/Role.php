<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Users;

#[ORM\Entity]
class Role
{

    #[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(name: "role_id", type: "integer")]
private ?int $id = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $name;

    #[ORM\Column(type: "string", length: 255)]
    private string $description;

    #[ORM\Column(type: "string", length: 20)]
    private string $status;

    #[ORM\Column(type: "string", length: 50)]
    private string $default_dashboard;

    public function getRole_id()
    {
        return $this->id;
    }

    public function setRole_id($value)
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

    public function getDefaultDashboard()
    {
        return $this->default_dashboard;
    }

    public function setDefaultDashboard($value)
    {
        $this->default_dashboard = $value;
    }

    #[ORM\OneToMany(mappedBy: "role_id", targetEntity: Users::class)]
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
