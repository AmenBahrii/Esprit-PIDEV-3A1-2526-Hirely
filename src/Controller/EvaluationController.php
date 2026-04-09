<?php

namespace App\Controller;

use App\Service\DatabaseService;
use App\Service\TemplateRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evaluations')]
class EvaluationController
{
    #[Route('', name: 'app_evaluations')]
    public function list(): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();
        
        $evaluations = $db->getEvaluations();
        
        $html = $renderer->render('evaluation/list.html.twig', [
            'evaluations' => $evaluations,
        ]);
        
        return new Response($html);
    }

    #[Route('/interview/{interviewId}/form', name: 'app_evaluation_form', methods: ['GET', 'POST'])]
    public function form(int $interviewId, Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $interview = $db->getInterviewById($interviewId);
        if (!$interview) {
            return new Response('Interview not found', 404);
        }

        // Check if interview is completed
        if ($interview['status'] !== 'completed') {
            return new Response('<script>alert("Evaluations can only be created for completed interviews."); window.location.href = "/interviews/' . $interviewId . '";</script>');
        }

        // Check if evaluation already exists
        $pdo = $db->getPDO();
        $stmt = $pdo->prepare("SELECT id FROM interview_evaluation WHERE interview_id = :id");
        $stmt->execute([':id' => $interviewId]);
        $existingEval = $stmt->fetch();

        if ($request->isMethod('POST')) {
            // Parse form data
            $scores = [];
            $comments = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'score_') === 0) {
                    $criteriaId = substr($key, 6);
                    $scores[$criteriaId] = $value;
                } elseif (strpos($key, 'comment_') === 0) {
                    $criteriaId = substr($key, 8);
                    $comments[$criteriaId] = $value;
                }
            }

            $data = [
                'interview_id' => $interviewId,
                'user_id' => 1,
                'overall_rating' => $request->request->get('overall_rating'),
                'recommendation' => $request->request->get('recommendation'),
                'hire_decision' => $request->request->get('hire_decision'),
                'strengths' => $request->request->get('strengths'),
                'weaknesses' => $request->request->get('weaknesses'),
                'scores' => $scores,
                'comments' => $comments,
            ];

            try {
                if ($existingEval) {
                    $db->updateEvaluation($existingEval['id'], $data);
                    $message = 'Evaluation updated successfully!';
                } else {
                    $db->createEvaluation($data);
                    $message = 'Evaluation submitted successfully!';
                }
                return new Response('<script>window.location.href = "/evaluations"; alert("' . $message . '");</script>');
            } catch (\Exception $e) {
                return new Response('Error: ' . $e->getMessage(), 500);
            }
        }

        $criteria = $db->getEvaluationCriteria();
        $evaluation = [];
        
        if ($existingEval) {
            $evaluation = $db->getEvaluationById($existingEval['id']);
        }

        $html = $renderer->render('evaluation/form.html.twig', [
            'interview' => $interview,
            'criteria' => $criteria,
            'evaluation' => $evaluation,
        ]);

        return new Response($html);
    }

    #[Route('/{id}', name: 'app_evaluation_show')]
    public function show(int $id): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $evaluation = $db->getEvaluationById($id);
        if (!$evaluation) {
            return new Response('Evaluation not found', 404);
        }

        $html = $renderer->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $evaluation = $db->getEvaluationById($id);
        if (!$evaluation) {
            return new Response('Evaluation not found', 404);
        }

        if ($request->isMethod('POST')) {
            $scores = [];
            $comments = [];
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'score_') === 0) {
                    $criteriaId = substr($key, 6);
                    $scores[$criteriaId] = $value;
                } elseif (strpos($key, 'comment_') === 0) {
                    $criteriaId = substr($key, 8);
                    $comments[$criteriaId] = $value;
                }
            }

            $data = [
                'overall_rating' => $request->request->get('overall_rating'),
                'recommendation' => $request->request->get('recommendation'),
                'hire_decision' => $request->request->get('hire_decision'),
                'strengths' => $request->request->get('strengths'),
                'weaknesses' => $request->request->get('weaknesses'),
                'scores' => $scores,
                'comments' => $comments,
            ];

            try {
                $db->updateEvaluation($id, $data);
                return new Response('<script>window.location.href = "/evaluations"; alert("Evaluation updated successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error: ' . $e->getMessage(), 500);
            }
        }

        $criteria = $db->getEvaluationCriteria();
        $interview = $db->getInterviewById($evaluation['interview_id']);

        $html = $renderer->render('evaluation/form.html.twig', [
            'interview' => $interview,
            'criteria' => $criteria,
            'evaluation' => $evaluation,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/delete', name: 'app_evaluation_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $db = new DatabaseService();

        try {
            $db->deleteEvaluation($id);
            return new Response('<script>window.location.href = "/evaluations"; alert("Evaluation deleted successfully!");</script>');
        } catch (\Exception $e) {
            return new Response('Error deleting evaluation: ' . $e->getMessage(), 500);
        }
    }
}
