<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Forum_notification
{

    #[ORM\Id]
    #[ORM\Column(type: "bigint")]
    private string $id;

    #[ORM\Column(type: "integer")]
    private int $recipient_user_id;

    #[ORM\Column(type: "integer")]
    private int $actor_user_id;

    #[ORM\Column(type: "string")]
    private string $type;

    #[ORM\Column(type: "bigint")]
    private string $post_id;

    #[ORM\Column(type: "bigint")]
    private string $comment_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $message;

    #[ORM\Column(type: "boolean")]
    private bool $is_read;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getRecipient_user_id()
    {
        return $this->recipient_user_id;
    }

    public function setRecipient_user_id($value)
    {
        $this->recipient_user_id = $value;
    }

    public function getActor_user_id()
    {
        return $this->actor_user_id;
    }

    public function setActor_user_id($value)
    {
        $this->actor_user_id = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getPost_id()
    {
        return $this->post_id;
    }

    public function setPost_id($value)
    {
        $this->post_id = $value;
    }

    public function getComment_id()
    {
        return $this->comment_id;
    }

    public function setComment_id($value)
    {
        $this->comment_id = $value;
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
}
