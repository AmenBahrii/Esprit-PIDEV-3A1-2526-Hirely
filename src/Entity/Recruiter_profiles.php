<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;

#[ORM\Entity]
class Recruiter_profiles
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $recruiter_id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "recruiter_profiless")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Users $user_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $first_name;

    #[ORM\Column(type: "string", length: 100)]
    private string $last_name;

    #[ORM\Column(type: "string", length: 100)]
    private string $department;

    #[ORM\Column(type: "string", length: 20)]
    private string $phone;

    public function getRecruiter_id()
    {
        return $this->recruiter_id;
    }

    public function setRecruiter_id($value)
    {
        $this->recruiter_id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getFirst_name()
    {
        return $this->first_name;
    }

    public function setFirst_name($value)
    {
        $this->first_name = $value;
    }

    public function getLast_name()
    {
        return $this->last_name;
    }

    public function setLast_name($value)
    {
        $this->last_name = $value;
    }

    public function getDepartment()
    {
        return $this->department;
    }

    public function setDepartment($value)
    {
        $this->department = $value;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function setPhone($value)
    {
        $this->phone = $value;
    }
}
