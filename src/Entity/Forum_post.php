<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Forum_post
{

    #[ORM\Id]
    #[ORM\Column(type: "bigint")]
    private string $id;

    #[ORM\Column(type: "integer")]
    private int $author_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "string", length: 100)]
    private string $tag;

    #[ORM\Column(type: "string", length: 20)]
    private string $status;

    #[ORM\Column(type: "boolean")]
    private bool $is_pinned;

    #[ORM\Column(type: "boolean")]
    private bool $is_locked;

    #[ORM\Column(type: "text")]
    private string $moderation_note;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $edited_at;

    #[ORM\Column(type: "integer")]
    private int $edited_by;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getAuthor_id()
    {
        return $this->author_id;
    }

    public function setAuthor_id($value)
    {
        $this->author_id = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setTag($value)
    {
        $this->tag = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getIs_pinned()
    {
        return $this->is_pinned;
    }

    public function setIs_pinned($value)
    {
        $this->is_pinned = $value;
    }

    public function getIs_locked()
    {
        return $this->is_locked;
    }

    public function setIs_locked($value)
    {
        $this->is_locked = $value;
    }

    public function getModeration_note()
    {
        return $this->moderation_note;
    }

    public function setModeration_note($value)
    {
        $this->moderation_note = $value;
    }

    public function getEdited_at()
    {
        return $this->edited_at;
    }

    public function setEdited_at($value)
    {
        $this->edited_at = $value;
    }

    public function getEdited_by()
    {
        return $this->edited_by;
    }

    public function setEdited_by($value)
    {
        $this->edited_by = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }

    public function getUpdated_at()
    {
        return $this->updated_at;
    }

    public function setUpdated_at($value)
    {
        $this->updated_at = $value;
    }
}
