<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Onboardingplan
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $planId;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $deadline;

    #[ORM\Column(type: "string", length: 80)]
    private string $qr_token;

    public function getPlanId()
    {
        return $this->planId;
    }

    public function setPlanId($value)
    {
        $this->planId = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getDeadline()
    {
        return $this->deadline;
    }

    public function setDeadline($value)
    {
        $this->deadline = $value;
    }

    public function getQr_token()
    {
        return $this->qr_token;
    }

    public function setQr_token($value)
    {
        $this->qr_token = $value;
    }
}
