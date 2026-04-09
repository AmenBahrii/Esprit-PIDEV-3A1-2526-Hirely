<?php

namespace App\Service;

use App\Entity\Interview;
use App\Entity\Application;
use App\Entity\InterviewType;
use App\Entity\User;
use App\Repository\InterviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class InterviewService
{
    public function __construct(
        private InterviewRepository $interviewRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function scheduleInterview(
        Application $application,
        User $recruiter,
        InterviewType $type,
        \DateTime $scheduleDate,
        int $duration,
        string $format,
        ?string $location,
        ?string $meetingLink,
        ?string $notes,
        ?int $round = null
    ): Interview {
        $interview = new Interview();
        $interview->setApplication($application);
        $interview->setRecruiter($recruiter);
        $interview->setInterviewType($type);
        $interview->setScheduleDate($scheduleDate);
        $interview->setDuration($duration);
        $interview->setFormat($format);
        $interview->setLocation($location);
        $interview->setMeetingLink($meetingLink);
        $interview->setNotes($notes);
        $interview->setRound($round);

        $this->entityManager->persist($interview);
        $this->entityManager->flush();

        return $interview;
    }

    public function getUpcomingInterviews(User $recruiter)
    {
        return $this->interviewRepository->findUpcomingByRecruiter($recruiter);
    }

    public function getTodayInterviews(User $recruiter)
    {
        return $this->interviewRepository->findTodayInterviews($recruiter);
    }

    public function markAsCompleted(Interview $interview): void
    {
        $interview->setStatus('completed');
        $this->entityManager->flush();
    }
}
