<?php

namespace App\Service;

use PDO;

class DatabaseService
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = new PDO(
            'mysql:host=127.0.0.1;dbname=hirely_interview_app',
            'root',
            ''
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getPDO(): PDO
    {
        return $this->pdo;
    }

    public function getInterviews(): array
    {
        $stmt = $this->pdo->query("
            SELECT i.id, a.candidate_name, it.name as interview_type, 
                   i.schedule_date, i.format, i.status
            FROM interview i
            LEFT JOIN application a ON i.application_id = a.id
            LEFT JOIN interview_type it ON i.interview_type_id = it.id
            ORDER BY i.schedule_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEvaluations(): array
    {
        $stmt = $this->pdo->query("
            SELECT ie.id, a.candidate_name, i.schedule_date,
                   ie.overall_rating, ie.recommendation, ie.hire_decision
            FROM interview_evaluation ie
            LEFT JOIN interview i ON ie.interview_id = i.id
            LEFT JOIN application a ON i.application_id = a.id
            ORDER BY ie.created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDashboardStats(): array
    {
        $totalInterviews = $this->pdo->query("SELECT COUNT(*) as count FROM interview")->fetch(PDO::FETCH_ASSOC)['count'];
        
        $todayInterviews = $this->pdo->query("
            SELECT COUNT(*) as count FROM interview 
            WHERE DATE(schedule_date) = CURDATE()
        ")->fetch(PDO::FETCH_ASSOC)['count'];
        
        $pendingEvals = $this->pdo->query("
            SELECT COUNT(*) as count FROM interview_evaluation 
            WHERE hire_decision = 'pending'
        ")->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'total_interviews' => $totalInterviews,
            'today_count' => $todayInterviews,
            'pending_evaluations_count' => $pendingEvals,
        ];
    }

    public function getUpcomingInterviews(): array
    {
        $stmt = $this->pdo->query("
            SELECT i.id, a.candidate_name, it.name as interview_type, 
                   i.schedule_date, i.format
            FROM interview i
            LEFT JOIN application a ON i.application_id = a.id
            LEFT JOIN interview_type it ON i.interview_type_id = it.id
            ORDER BY i.schedule_date ASC
            LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNotifications(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, message, type, is_read
            FROM notification
            WHERE is_read = 0
            ORDER BY created_at DESC
            LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Interview CRUD Methods
    public function createInterview(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO interview (application_id, user_id, interview_type_id, schedule_date, format, location, meeting_link, status)
            VALUES (:application_id, :user_id, :interview_type_id, :schedule_date, :format, :location, :meeting_link, :status)
        ");
        
        $stmt->execute([
            ':application_id' => $data['application_id'],
            ':user_id' => $data['user_id'] ?? 1,
            ':interview_type_id' => $data['interview_type_id'],
            ':schedule_date' => $data['schedule_date'],
            ':format' => $data['format'],
            ':location' => $data['location'] ?? '',
            ':meeting_link' => $data['meeting_link'] ?? '',
            ':status' => $data['status'] ?? 'scheduled',
        ]);
        
        return $this->pdo->lastInsertId();
    }

    public function updateInterview(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE interview 
            SET application_id = :application_id,
                interview_type_id = :interview_type_id,
                schedule_date = :schedule_date,
                format = :format,
                location = :location,
                meeting_link = :meeting_link,
                status = :status
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id' => $id,
            ':application_id' => $data['application_id'],
            ':interview_type_id' => $data['interview_type_id'],
            ':schedule_date' => $data['schedule_date'],
            ':format' => $data['format'],
            ':location' => $data['location'] ?? '',
            ':meeting_link' => $data['meeting_link'] ?? '',
            ':status' => $data['status'] ?? 'scheduled',
        ]);
    }

    public function deleteInterview(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM interview WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getInterviewById(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT i.*, a.candidate_name, it.name as interview_type_name
            FROM interview i
            LEFT JOIN application a ON i.application_id = a.id
            LEFT JOIN interview_type it ON i.interview_type_id = it.id
            WHERE i.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function getApplications(): array
    {
        $stmt = $this->pdo->query("SELECT id, candidate_name, job_id, status FROM application ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInterviewTypes(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, description FROM interview_type");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Evaluation CRUD Methods
    public function createEvaluation(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO interview_evaluation (interview_id, user_id, overall_rating, recommendation, hire_decision, strengths, weaknesses)
            VALUES (:interview_id, :user_id, :overall_rating, :recommendation, :hire_decision, :strengths, :weaknesses)
        ");
        
        $stmt->execute([
            ':interview_id' => $data['interview_id'],
            ':user_id' => $data['user_id'] ?? 1,
            ':overall_rating' => $data['overall_rating'],
            ':recommendation' => $data['recommendation'],
            ':hire_decision' => $data['hire_decision'] ?? 'pending',
            ':strengths' => $data['strengths'],
            ':weaknesses' => $data['weaknesses'],
        ]);
        
        $evaluationId = $this->pdo->lastInsertId();

        // Insert evaluation scores
        if (isset($data['scores']) && is_array($data['scores'])) {
            $scoreStmt = $this->pdo->prepare("
                INSERT INTO evaluation_score (interview_evaluation_id, evaluation_criteria_id, score, comment)
                VALUES (:evaluation_id, :criteria_id, :score, :comment)
            ");
            
            foreach ($data['scores'] as $criteriaId => $score) {
                $scoreStmt->execute([
                    ':evaluation_id' => $evaluationId,
                    ':criteria_id' => $criteriaId,
                    ':score' => $score,
                    ':comment' => $data['comments'][$criteriaId] ?? '',
                ]);
            }
        }

        return $evaluationId;
    }

    public function updateEvaluation(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE interview_evaluation 
            SET overall_rating = :overall_rating,
                recommendation = :recommendation,
                hire_decision = :hire_decision,
                strengths = :strengths,
                weaknesses = :weaknesses
            WHERE id = :id
        ");
        
        $result = $stmt->execute([
            ':id' => $id,
            ':overall_rating' => $data['overall_rating'],
            ':recommendation' => $data['recommendation'],
            ':hire_decision' => $data['hire_decision'],
            ':strengths' => $data['strengths'],
            ':weaknesses' => $data['weaknesses'],
        ]);

        // Delete old scores
        $this->pdo->prepare("DELETE FROM evaluation_score WHERE interview_evaluation_id = :id")->execute([':id' => $id]);

        // Insert new scores
        if (isset($data['scores']) && is_array($data['scores'])) {
            $scoreStmt = $this->pdo->prepare("
                INSERT INTO evaluation_score (interview_evaluation_id, evaluation_criteria_id, score, comment)
                VALUES (:evaluation_id, :criteria_id, :score, :comment)
            ");
            
            foreach ($data['scores'] as $criteriaId => $score) {
                $scoreStmt->execute([
                    ':evaluation_id' => $id,
                    ':criteria_id' => $criteriaId,
                    ':score' => $score,
                    ':comment' => $data['comments'][$criteriaId] ?? '',
                ]);
            }
        }

        return $result;
    }

    public function deleteEvaluation(int $id): bool
    {
        $this->pdo->prepare("DELETE FROM evaluation_score WHERE interview_evaluation_id = :id")->execute([':id' => $id]);
        $stmt = $this->pdo->prepare("DELETE FROM interview_evaluation WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getEvaluationById(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT ie.*, i.id as interview_id, i.schedule_date, i.format, i.location,
                   a.candidate_name, it.name as interview_type_name
            FROM interview_evaluation ie
            LEFT JOIN interview i ON ie.interview_id = i.id
            LEFT JOIN application a ON i.application_id = a.id
            LEFT JOIN interview_type it ON i.interview_type_id = it.id
            WHERE ie.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $evaluation = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($evaluation) {
            // Get scores
            $scoreStmt = $this->pdo->prepare("
                SELECT es.*, ec.name, ec.description, ec.weight
                FROM evaluation_score es
                LEFT JOIN evaluation_criteria ec ON es.evaluation_criteria_id = ec.id
                WHERE es.interview_evaluation_id = :id
            ");
            $scoreStmt->execute([':id' => $id]);
            $scores = $scoreStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform scores into associative array keyed by criteria_id for template access
            $scoresMap = [];
            $commentsMap = [];
            foreach ($scores as $scoreRow) {
                $criteriaId = $scoreRow['evaluation_criteria_id'];
                $scoresMap[$criteriaId] = $scoreRow['score'];
                $commentsMap[$criteriaId] = $scoreRow['comment'] ?? '';
            }
            
            $evaluation['scores'] = $scoresMap;
            $evaluation['comments'] = $commentsMap;
            $evaluation['scores_full'] = $scores; // Keep full scores for show template
        }

        return $evaluation ?: [];
    }

    public function getEvaluationCriteria(): array
    {
        $stmt = $this->pdo->query("SELECT id, name, description, weight FROM evaluation_criteria");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Application CRUD Methods
    public function createApplication(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO application (candidate_name, job_id, status, created_at)
            VALUES (:candidate_name, :job_id, :status, NOW())
        ");
        
        $stmt->execute([
            ':candidate_name' => $data['candidate_name'],
            ':job_id' => $data['job_id'] ?? null,
            ':status' => $data['status'] ?? 'applied',
        ]);
        
        return $this->pdo->lastInsertId();
    }

    public function updateApplication(int $id, array $data): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE application 
            SET candidate_name = :candidate_name,
                job_id = :job_id,
                status = :status
            WHERE id = :id
        ");
        
        return $stmt->execute([
            ':id' => $id,
            ':candidate_name' => $data['candidate_name'],
            ':job_id' => $data['job_id'] ?? null,
            ':status' => $data['status'] ?? 'applied',
        ]);
    }

    public function deleteApplication(int $id): bool
    {
        // First delete related interviews and evaluations
        $stmt = $this->pdo->prepare("
            SELECT id FROM interview WHERE application_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($interviews as $interview) {
            $this->deleteInterview($interview['id']);
        }
        
        $stmt = $this->pdo->prepare("DELETE FROM application WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getApplicationById(int $id): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM application WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}

