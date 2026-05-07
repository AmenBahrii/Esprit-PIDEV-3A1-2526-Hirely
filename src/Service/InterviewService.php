<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Interviews;
use App\Entity\Interview_types;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class InterviewService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function scheduleInterview(
        Application $application,
        Users $recruiter,
        int $typeId,
        \DateTime $scheduledDate,
        ?string $scheduledTime,
        int $durationMinutes,
        ?string $location,
        ?string $meetingLink,
        int $interviewRound = 1
    ): array {
        try {
            $interviewType = $this->entityManager->getRepository(Interview_types::class)->find($typeId);
            if (!$interviewType) {
                return ['success' => false, 'message' => 'Interview type not found', 'data' => null];
            }

            $interview = new Interviews();
            $interview->setApplication_id($application);
            $interview->setRecruiter_id($recruiter);
            $interview->setInterview_type_id($interviewType);
            $interview->setScheduled_date($scheduledDate);
            $interview->setScheduled_time($scheduledTime !== null && $scheduledTime !== '' ? $scheduledTime : null);
            $interview->setDuration_minutes($durationMinutes);
            $interview->setLocation($location);
            $interview->setMeeting_link($meetingLink);
            $interview->setStatus('scheduled');
            $interview->setInterview_round($interviewRound);
            $interview->setCreated_at(new \DateTime());
            $interview->setUpdated_at(new \DateTime());

            $this->entityManager->persist($interview);
            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Interview scheduled successfully', 'data' => $interview];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function updateInterview(
        Interviews $interview,
        ?int $typeId = null,
        ?\DateTime $scheduledDate = null,
        ?string $scheduledTime = null,
        ?int $durationMinutes = null,
        ?string $location = null,
        ?string $meetingLink = null,
        ?string $notes = null
    ): array {
        try {
            if ($typeId !== null) {
                $interviewType = $this->entityManager->getRepository(Interview_types::class)->find($typeId);
                if (!$interviewType) {
                    return ['success' => false, 'message' => 'Interview type not found', 'data' => null];
                }
                $interview->setInterview_type_id($interviewType);
            }

            if ($scheduledDate !== null) {
                $interview->setScheduled_date($scheduledDate);
            }

            if ($scheduledTime !== null) {
                $interview->setScheduled_time($scheduledTime !== '' ? $scheduledTime : null);
            }

            if ($durationMinutes !== null) {
                $interview->setDuration_minutes($durationMinutes);
            }

            if ($location !== null) {
                $interview->setLocation($location);
            }

            if ($meetingLink !== null) {
                $interview->setMeeting_link($meetingLink);
            }

            if ($notes !== null) {
                $interview->setNotes($notes);
            }

            $interview->setUpdated_at(new \DateTime());
            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Interview updated successfully', 'data' => $interview];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function completeInterview(Interviews $interview): array
    {
        try {
            if ($interview->getStatus() !== 'scheduled') {
                return ['success' => false, 'message' => 'Only scheduled interviews can be completed', 'data' => null];
            }

            $interview->setStatus('completed');
            $interview->setUpdated_at(new \DateTime());
            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Interview marked as completed', 'data' => $interview];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function cancelInterview(Interviews $interview): array
    {
        try {
            if (in_array($interview->getStatus(), ['completed', 'cancelled'])) {
                return ['success' => false, 'message' => 'Cannot cancel completed or already cancelled interviews', 'data' => null];
            }

            $interview->setStatus('cancelled');
            $interview->setUpdated_at(new \DateTime());
            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Interview cancelled', 'data' => $interview];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function getRecruiterInterviews(Users $recruiter): array
    {
        return $this->entityManager->getRepository(Interviews::class)
            ->findBy(['recruiter_id' => $recruiter], ['scheduled_date' => 'DESC']);
    }

    public function getInterviewsByApplication(Application $application): array
    {
        return $this->entityManager->getRepository(Interviews::class)
            ->findBy(['application_id' => $application], ['interview_round' => 'ASC']);
    }

    public function getInterviewsByType(int $typeId): array
    {
        return $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT i.* FROM interviews i WHERE i.interview_type_id = :typeId ORDER BY i.scheduled_date DESC',
            ['typeId' => $typeId]
        );
    }
}
