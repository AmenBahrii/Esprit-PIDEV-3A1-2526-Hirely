<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Interviews;

#[ORM\Entity]
class Notifications
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $notification_id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "notificationss")]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Users $user_id;

        #[ORM\ManyToOne(targetEntity: Interviews::class, inversedBy: "notificationss")]
    #[ORM\JoinColumn(name: 'interview_id', referencedColumnName: 'interview_id', onDelete: 'CASCADE')]
    private Interviews $interview_id;

    #[ORM\Column(type: "string", length: 100)]
    private string $notification_type;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $message;

    #[ORM\Column(type: "boolean")]
    private bool $is_read;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $read_at;

    public function getNotification_id()
    {
        return $this->notification_id;
    }

    public function setNotification_id($value)
    {
        $this->notification_id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getInterview_id()
    {
        return $this->interview_id;
    }

    public function setInterview_id($value)
    {
        $this->interview_id = $value;
    }

    public function getNotification_type()
    {
        return $this->notification_type;
    }

    public function setNotification_type($value)
    {
        $this->notification_type = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage($value)
    {
        $this->message = $value;
    }

    public function getIs_read()
    {
        return $this->is_read;
    }

    public function setIs_read($value)
    {
        $this->is_read = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getRead_at()
    {
        return $this->read_at;
    }

    public function setRead_at($value)
    {
        $this->read_at = $value;
    }
}
