<?php

namespace App\Service;

use App\Entity\Evaluation_criteria;
use App\Entity\Evaluation_scores;
use App\Entity\Interview_evaluations;
use App\Entity\Interviews;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;

class InterviewEvaluationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    private function calculateOverallRatingFromScores(array $scores): float
    {
        $scoreValues = [];

        foreach ($scores as $scoreData) {
            if (isset($scoreData['score']) && $scoreData['score'] !== '' && $scoreData['score'] !== null) {
                $scoreValues[] = (float) $scoreData['score'];
            }
        }

        if (count($scoreValues) === 0) {
            return 0.0;
        }

        return round(array_sum($scoreValues) / count($scoreValues), 2);
    }

    public function createEvaluation(
        Interviews $interview,
        Users $recruiter,
        array $scores, // ['criteria_id' => ['score' => 4.5, 'comment' => '...']]
        string $recommendation,
        string $hireDecision,
        string $strengths,
        string $weaknesses,
        string $generalComments,
        string $nextSteps,
        bool $isDraft = false
    ): array {
        try {
            if ($interview->getStatus() !== 'completed') {
                return ['success' => false, 'message' => 'Can only evaluate completed interviews', 'data' => null];
            }

            $overallRating = $this->calculateOverallRatingFromScores($scores);

            $evaluation = new Interview_evaluations();
            $evaluation->setInterview_id($interview);
            $evaluation->setRecruiter_id($recruiter);
            $evaluation->setOverall_rating($overallRating);
            $evaluation->setRecommendation($recommendation);
            $evaluation->setHire_decision($hireDecision);
            $evaluation->setStrengths($strengths);
            $evaluation->setWeaknesses($weaknesses);
            $evaluation->setGeneral_comments($generalComments);
            $evaluation->setNext_steps($nextSteps);
            $evaluation->setIs_draft($isDraft);
            $evaluation->setEvaluated_at(new \DateTime());
            $evaluation->setUpdated_at(new \DateTime());

            $this->entityManager->persist($evaluation);
            $this->entityManager->flush();

            // Add scores
            foreach ($scores as $criteriaId => $scoreData) {
                $criteria = $this->entityManager->getRepository(Evaluation_criteria::class)->find($criteriaId);
                if (!$criteria) continue;

                $score = new Evaluation_scores();
                $score->setEvaluation_id($evaluation);
                $score->setCriteria_id($criteria);
                $score->setScore((float)$scoreData['score']);
                $score->setComments($scoreData['comment'] ?? null);

                $this->entityManager->persist($score);
            }

            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Evaluation created successfully', 'data' => $evaluation];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function updateEvaluation(
        Interview_evaluations $evaluation,
        array $scores,
        string $recommendation,
        string $hireDecision,
        string $strengths,
        string $weaknesses,
        string $generalComments,
        string $nextSteps
    ): array {
        try {
            if (!$evaluation->getIs_draft()) {
                return ['success' => false, 'message' => 'Cannot edit submitted evaluations', 'data' => null];
            }

            $evaluation->setOverall_rating($this->calculateOverallRatingFromScores($scores));

            $evaluation->setRecommendation($recommendation);
            $evaluation->setHire_decision($hireDecision);
            $evaluation->setStrengths($strengths);
            $evaluation->setWeaknesses($weaknesses);
            $evaluation->setGeneral_comments($generalComments);
            $evaluation->setNext_steps($nextSteps);
            $evaluation->setUpdated_at(new \DateTime());

            // Delete existing scores
            $existingScores = $this->entityManager->getRepository(Evaluation_scores::class)
                ->findBy(['evaluation_id' => $evaluation]);
            foreach ($existingScores as $score) {
                $this->entityManager->remove($score);
            }

            $this->entityManager->flush();

            // Re-add scores
            foreach ($scores as $criteriaId => $scoreData) {
                $criteria = $this->entityManager->getRepository(Evaluation_criteria::class)->find($criteriaId);
                if (!$criteria) continue;

                $score = new Evaluation_scores();
                $score->setEvaluation_id($evaluation);
                $score->setCriteria_id($criteria);
                $score->setScore((float)$scoreData['score']);
                $score->setComments($scoreData['comment'] ?? null);

                $this->entityManager->persist($score);
            }

            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Evaluation updated successfully', 'data' => $evaluation];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function submitEvaluation(Interview_evaluations $evaluation): array
    {
        try {
            if (!$evaluation->getIs_draft()) {
                return ['success' => false, 'message' => 'Evaluation already submitted', 'data' => null];
            }

            $evaluation->setIs_draft(false);
            $evaluation->setUpdated_at(new \DateTime());
            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Evaluation submitted', 'data' => $evaluation];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }

    public function calculateOverallRating(Interview_evaluations $evaluation): float
    {
        $scores = $this->entityManager->getRepository(Evaluation_scores::class)
            ->findBy(['evaluation_id' => $evaluation]);
        
        if (count($scores) === 0) {
            return 0;
        }

        $total = array_sum(array_map(fn($s) => $s->getScore(), $scores));
        return round($total / count($scores), 2);
    }

    public function getRecruiterEvaluations(Users $recruiter): array
    {
        return $this->entityManager->getRepository(Interview_evaluations::class)
            ->findBy(['recruiter_id' => $recruiter], ['evaluated_at' => 'DESC']);
    }

    public function getPendingEvaluations(Users $recruiter): array
    {
        return $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT ie.*, i.*, a.* FROM interview_evaluations ie
             INNER JOIN interviews i ON ie.interview_id = i.interview_id
             INNER JOIN application a ON i.application_id = a.application_id
             WHERE ie.recruiter_id = :recruiter_id AND ie.is_draft = true
             ORDER BY ie.evaluated_at DESC',
            ['recruiter_id' => $recruiter->getId()]
        );
    }

    public function canEvaluateInterview(Interviews $interview): bool
    {
        return $interview->getStatus() === 'completed';
    }

    public function deleteEvaluation(Interview_evaluations $evaluation): array
    {
        try {
            if (!$evaluation->getIs_draft()) {
                return ['success' => false, 'message' => 'Cannot delete submitted evaluations', 'data' => null];
            }

            // Delete associated scores
            $scores = $this->entityManager->getRepository(Evaluation_scores::class)
                ->findBy(['evaluation_id' => $evaluation]);
            foreach ($scores as $score) {
                $this->entityManager->remove($score);
            }

            $this->entityManager->remove($evaluation);
            $this->entityManager->flush();

            return ['success' => true, 'message' => 'Evaluation deleted', 'data' => null];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage(), 'data' => null];
        }
    }
}
