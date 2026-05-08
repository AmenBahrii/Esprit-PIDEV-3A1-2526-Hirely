<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Interview_types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Notifications;

#[ORM\Entity]
class Interviews
{
    public function __construct()
    {
        $this->interview_evaluationss = new ArrayCollection();
        $this->notificationss = new ArrayCollection();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $interview_id;

        #[ORM\ManyToOne(targetEntity: Application::class, inversedBy: "interviewss")]
    #[ORM\JoinColumn(name: 'application_id', referencedColumnName: 'applicationId', onDelete: 'CASCADE')]
    private Application $application_id;

        #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: "interviewss")]
    #[ORM\JoinColumn(name: 'recruiter_id', referencedColumnName: 'user_id', onDelete: 'CASCADE')]
    private Users $recruiter_id;

        #[ORM\ManyToOne(targetEntity: Interviewee_profiles::class, inversedBy: "interviewss")]
    #[ORM\JoinColumn(name: 'interviewee_id', referencedColumnName: 'interviewee_id', onDelete: 'CASCADE')]
    private Interviewee_profiles $interviewee_id;

        #[ORM\ManyToOne(targetEntity: Interview_types::class, inversedBy: "interviewss")]
    #[ORM\JoinColumn(name: 'interview_type_id', referencedColumnName: 'interview_type_id', onDelete: 'CASCADE')]
    private Interview_types $interview_type_id;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $scheduled_date;

    #[ORM\Column(type: "string", nullable: true)]
    private ?string $scheduled_time = null;

    #[ORM\Column(type: "integer")]
    private int $duration_minutes;

    #[ORM\Column(type: "string", length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: "string", length: 500, nullable: true)]
    private ?string $meeting_link = null;

    #[ORM\Column(type: "string", length: 30)]
    private string $status;

    #[ORM\Column(type: "integer")]
    private int $interview_round;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $updated_at;

    public function getInterview_id()
    {
        return $this->interview_id;
    }

    public function setInterview_id($value)
    {
        $this->interview_id = $value;
    }

    public function getApplication_id()
    {
        return $this->application_id;
    }

    public function setApplication_id($value)
    {
        $this->application_id = $value;
    }

    public function getRecruiter_id()
    {
        return $this->recruiter_id;
    }

    public function setRecruiter_id($value)
    {
        $this->recruiter_id = $value;
    }

    public function getInterviewee_id()
    {
        return $this->interviewee_id;
    }

    public function setInterviewee_id($value)
    {
        $this->interviewee_id = $value;
    }

    public function getInterview_type_id()
    {
        return $this->interview_type_id;
    }

    public function setInterview_type_id($value)
    {
        $this->interview_type_id = $value;
    }

    public function getScheduled_date()
    {
        return $this->scheduled_date;
    }

    public function setScheduled_date($value)
    {
        $this->scheduled_date = $value;
    }

    public function getScheduled_time()
    {
        return $this->scheduled_time;
    }

    public function setScheduled_time($value)
    {
        $this->scheduled_time = $value;
    }

    public function getDuration_minutes()
    {
        return $this->duration_minutes;
    }

    public function setDuration_minutes($value)
    {
        $this->duration_minutes = $value;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setLocation($value)
    {
        $this->location = $value;
    }

    public function getMeeting_link()
    {
        return $this->meeting_link;
    }

    public function setMeeting_link($value)
    {
        $this->meeting_link = $value;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($value)
    {
        $this->status = $value;
    }

    public function getInterview_round()
    {
        return $this->interview_round;
    }

    public function setInterview_round($value)
    {
        $this->interview_round = $value;
    }

    public function getNotes()
    {
        return $this->notes;
    }

    public function setNotes($value)
    {
        $this->notes = $value;
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

    #[ORM\OneToMany(mappedBy: "interview_id", targetEntity: Interview_evaluations::class)]
    private Collection $interview_evaluationss;

    #[ORM\OneToMany(mappedBy: "interview_id", targetEntity: Notifications::class)]
    private Collection $notificationss;
}
