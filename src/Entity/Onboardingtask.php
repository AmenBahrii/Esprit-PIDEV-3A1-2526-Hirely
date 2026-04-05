<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Onboardingtask
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $taskId;

    #[ORM\Column(type: "integer")]
    private int $planId;

    #[ORM\Column(type: "string", length: 255)]
    private string $title;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(type: "string")]
    private string $status;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $deadline;

    #[ORM\Column(type: "string", length: 255)]
    private string $filePath;

    #[ORM\Column(type: "string", length: 255)]
    private string $cloudinary_public_id;

    #[ORM\Column(type: "string", length: 255)]
    private string $original_file_name;

    #[ORM\Column(type: "string", length: 120)]
    private string $content_type;

    public function getTaskId()
    {
        return $this->taskId;
    }

    public function setTaskId($value)
    {
        $this->taskId = $value;
    }

    public function getPlanId()
    {
        return $this->planId;
    }

    public function setPlanId($value)
    {
        $this->planId = $value;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($value)
    {
        $this->title = $value;
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

    public function getDeadline()
    {
        return $this->deadline;
    }

    public function setDeadline($value)
    {
        $this->deadline = $value;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    public function setFilePath($value)
    {
        $this->filePath = $value;
    }

    public function getCloudinary_public_id()
    {
        return $this->cloudinary_public_id;
    }

    public function setCloudinary_public_id($value)
    {
        $this->cloudinary_public_id = $value;
    }

    public function getOriginal_file_name()
    {
        return $this->original_file_name;
    }

    public function setOriginal_file_name($value)
    {
        $this->original_file_name = $value;
    }

    public function getContent_type()
    {
        return $this->content_type;
    }

    public function setContent_type($value)
    {
        $this->content_type = $value;
    }
}
