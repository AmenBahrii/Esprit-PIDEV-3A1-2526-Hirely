<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Users;

#[ORM\Entity]
class Password_reset_otp
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "password_reset_otps")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Users $user_id;

    #[ORM\Column(type: "string", length: 6)]
    private string $otp_code;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $expires_at;

    #[ORM\Column(type: "boolean")]
    private bool $used;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getOtp_code()
    {
        return $this->otp_code;
    }

    public function setOtp_code($value)
    {
        $this->otp_code = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getExpires_at()
    {
        return $this->expires_at;
    }

    public function setExpires_at($value)
    {
        $this->expires_at = $value;
    }

    public function getUsed()
    {
        return $this->used;
    }

    public function setUsed($value)
    {
        $this->used = $value;
    }
}
