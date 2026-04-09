<?php

namespace App\Service;

use App\Entity\Interview;
use App\Entity\InterviewEvaluation;
use App\Entity\EvaluationScore;
use App\Entity\EvaluationCriteria;
use App\Entity\User;
use App\Repository\InterviewEvaluationRepository;
use App\Repository\EvaluationCriteriaRepository;
use Doctrine\ORM\EntityManagerInterface;

class EvaluationService
{
    public function __construct(
        private InterviewEvaluationRepository $evaluationRepository,
        private EvaluationCriteriaRepository $criteriaRepository,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createEvaluation(
        Interview $interview,
        User $recruiter,
        array $criteriaScores,
        string $recommendation,
        string $hireDecision,
        string $strengths,
        string $weaknesses,
        string $comments,
        string $nextSteps
    ): InterviewEvaluation {
        $evaluation = new InterviewEvaluation();
        $evaluation->setInterview($interview);
        $evaluation->setRecruiter($recruiter);
        $evaluation->setRecommendation($recommendation);
        $evaluation->setHireDecision($hireDecision);
        $evaluation->setStrengths($strengths);
        $evaluation->setWeaknesses($weaknesses);
        $evaluation->setComments($comments);
        $evaluation->setNextSteps($nextSteps);

        // Add criteria scores
        $totalScore = 0;
        $scoreCount = 0;

        foreach ($criteriaScores as $criteriaId => $score) {
            $criteria = $this->criteriaRepository->find($criteriaId);
            if ($criteria) {
                $evaluationScore = new EvaluationScore();
                $evaluationScore->setCriteria($criteria);
                $evaluationScore->setScore($score['score']);
                $evaluationScore->setComment($score['comment'] ?? null);
                $evaluationScore->setEvaluation($evaluation);
                
                $evaluation->addScore($evaluationScore);
                $totalScore += $score['score'];
                $scoreCount++;
            }
        }

        if ($scoreCount > 0) {
            $evaluation->setOverallRating($totalScore / $scoreCount);
        }

        $evaluation->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($evaluation);
        $this->entityManager->flush();

        return $evaluation;
    }

    public function getPendingEvaluations(User $recruiter)
    {
        return $this->evaluationRepository->findPendingByRecruiter($recruiter);
    }

    public function getEvaluationsByRecruiter(User $recruiter)
    {
        return $this->evaluationRepository->findByRecruiter($recruiter);
    }

    public function getAllCriteria()
    {
        return $this->criteriaRepository->findAll();
    }
}
