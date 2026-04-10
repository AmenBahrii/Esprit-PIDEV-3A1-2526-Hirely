<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Forum_interaction
{

    #[ORM\Id]
    #[ORM\Column(type: "bigint")]
    private string $id;

    #[ORM\Column(type: "string")]
    private string $target_type;

    #[ORM\Column(type: "bigint")]
    private string $target_id;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "string")]
    private string $interaction_type;

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

    public function getTarget_type()
    {
        return $this->target_type;
    }

    public function setTarget_type($value)
    {
        $this->target_type = $value;
    }

    public function getTarget_id()
    {
        return $this->target_id;
    }

    public function setTarget_id($value)
    {
        $this->target_id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getInteraction_type()
    {
        return $this->interaction_type;
    }

    public function setInteraction_type($value)
    {
        $this->interaction_type = $value;
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
