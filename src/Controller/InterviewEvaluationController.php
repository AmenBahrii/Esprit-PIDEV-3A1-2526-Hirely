<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Entity\Evaluation_criteria;
use App\Entity\Interview_evaluations;
use App\Entity\Interviews;
use App\Entity\Users;
use App\Service\InterviewEvaluationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/evaluation')]
final class InterviewEvaluationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InterviewEvaluationService $evaluationService,
    ) {}

    #[Route(name: 'app_evaluation_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->requireCurrentUser();
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName === 'admin') {
            $evaluations = $this->entityManager->getConnection()->fetchAllAssociative(
                'SELECT
                    ie.evaluation_id,
                    ie.overall_rating,
                    ie.hire_decision,
                    ie.is_draft,
                    ie.evaluated_at,
                    COALESCE(r.first_name, "") AS recruiter_first_name,
                    COALESCE(r.last_name, "") AS recruiter_last_name,
                    COALESCE(c.first_name, "") AS candidate_first_name,
                    COALESCE(c.last_name, "") AS candidate_last_name,
                    COALESCE(j.title, "N/A") AS job_title
                 FROM interview_evaluations ie
                 LEFT JOIN users r ON ie.recruiter_id = r.user_id
                 LEFT JOIN interviews i ON ie.interview_id = i.interview_id
                 LEFT JOIN application a ON i.application_id = a.applicationId
                 LEFT JOIN users c ON a.user_id = c.user_id
                 LEFT JOIN joboffer j ON a.jobOfferId = j.jobOfferId
                 ORDER BY ie.evaluated_at DESC'
            );
            return $this->render('admin/evaluation/index.html.twig', ['evaluations' => $evaluations]);
        }

        if ($roleName === 'recruiter') {
            $evaluations = $this->evaluationService->getRecruiterEvaluations($user);
            return $this->render('recruiter/evaluation/index.html.twig', ['evaluations' => $evaluations]);
        }

        return $this->render('candidate/evaluation/index.html.twig', ['evaluations' => []]);
    }

    #[Route('/interview/{interviewId}/evaluate', name: 'app_evaluation_new', methods: ['GET', 'POST'])]
    public function evaluate(int $interviewId, Request $request): Response
    {
        $user = $this->requireCurrentUser();
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName !== 'recruiter' && $roleName !== 'admin') {
            return $this->redirectToRoute('app_evaluation_index');
        }

        $interview = $this->entityManager->getRepository(Interviews::class)->find($interviewId);
        if (!$interview) {
            throw $this->createNotFoundException('Interview not found');
        }

        if (!$this->evaluationService->canEvaluateInterview($interview)) {
            $this->addFlash('error', 'Can only evaluate completed interviews');
            return $this->redirectToRoute('app_interview_show', ['interviewId' => $interviewId]);
        }

        if ($request->isMethod('POST')) {
            $scores = [];
            foreach ($request->request->all() as $key => $value) {
                if (strpos($key, 'criteria_') === 0 || strpos($key, 'score_') === 0) {
                    $criteriaId = strpos($key, 'criteria_') === 0
                        ? str_replace('criteria_', '', $key)
                        : str_replace('score_', '', $key);
                    $scores[$criteriaId] = [
                        'score' => $value,
                        'comment' => $request->request->get('comment_' . $criteriaId),
                    ];
                }
            }

            $result = $this->evaluationService->createEvaluation(
                $interview,
                $user,
                $scores,
                $request->request->get('recommendation'),
                $request->request->get('hire_decision'),
                $request->request->get('strengths'),
                $request->request->get('weaknesses'),
                $request->request->get('general_comments'),
                $request->request->get('next_steps'),
                (bool)$request->request->get('is_draft', false)
            );

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_evaluation_show', ['evaluationId' => $result['data']->getEvaluation_id()]);
            }

            $this->addFlash('error', $result['message']);
        }

        $criteria = $this->entityManager->getRepository(Evaluation_criteria::class)->findBy(['is_active' => true], ['display_order' => 'ASC']);

        return $this->render('recruiter/evaluation/new.html.twig', [
            'interview' => $interview,
            'criteria' => $criteria,
        ]);
    }

    #[Route('/{evaluationId}', name: 'app_evaluation_show', methods: ['GET'])]
    public function show(int $evaluationId): Response
    {
        $evaluation = $this->entityManager->getRepository(Interview_evaluations::class)->find($evaluationId);

        if (!$evaluation) {
            throw $this->createNotFoundException('Evaluation not found');
        }

        // Get associated scores
        $scores = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT es.*, ec.criteria_name, ec.max_score FROM evaluation_scores es
             LEFT JOIN evaluation_criteria ec ON es.criteria_id = ec.criteria_id
             WHERE es.evaluation_id = :evaluationId',
            ['evaluationId' => $evaluationId]
        );

        return $this->render('recruiter/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
            'scores' => $scores,
        ]);
    }

    #[Route('/{evaluationId}/edit', name: 'app_evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(int $evaluationId, Request $request): Response
    {
        $user = $this->requireCurrentUser();
        $evaluation = $this->entityManager->getRepository(Interview_evaluations::class)->find($evaluationId);

        if (!$evaluation) {
            throw $this->createNotFoundException('Evaluation not found');
        }

        if ($evaluation->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_evaluation_index');
        }

        if ($request->isMethod('POST')) {
            $scores = [];

            foreach ($request->request->all() as $key => $value) {
                if (strpos($key, 'criteria_') === 0 || strpos($key, 'score_') === 0) {
                    $criteriaId = strpos($key, 'criteria_') === 0
                        ? str_replace('criteria_', '', $key)
                        : str_replace('score_', '', $key);

                    $scores[$criteriaId] = [
                        'score' => $value,
                        'comment' => $request->request->get('comment_' . $criteriaId),
                    ];
                }
            }

            $result = $this->evaluationService->updateEvaluation(
                $evaluation,
                $scores,
                (string) $request->request->get('recommendation', ''),
                (string) $request->request->get('hire_decision', ''),
                (string) $request->request->get('strengths', ''),
                (string) $request->request->get('weaknesses', ''),
                (string) $request->request->get('general_comments', ''),
                (string) $request->request->get('next_steps', '')
            );

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_evaluation_show', ['evaluationId' => $evaluationId]);
            }

            $this->addFlash('error', $result['message']);
        }

        $criteria = $this->entityManager->getRepository(Evaluation_criteria::class)->findBy(['is_active' => true], ['display_order' => 'ASC']);
        $scores = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT es.*, ec.criteria_name FROM evaluation_scores es
             LEFT JOIN evaluation_criteria ec ON es.criteria_id = ec.criteria_id
             WHERE es.evaluation_id = :evaluationId',
            ['evaluationId' => $evaluationId]
        );

        return $this->render('recruiter/evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'criteria' => $criteria,
            'scores' => $scores,
        ]);
    }

    #[Route('/{evaluationId}/submit', name: 'app_evaluation_submit', methods: ['POST'])]
    public function submit(int $evaluationId): Response
    {
        $user = $this->requireCurrentUser();
        $evaluation = $this->entityManager->getRepository(Interview_evaluations::class)->find($evaluationId);

        if (!$evaluation) {
            throw $this->createNotFoundException('Evaluation not found');
        }

        // Authorization check
        if ($evaluation->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_evaluation_index');
        }

        $result = $this->evaluationService->submitEvaluation($evaluation);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_evaluation_show', ['evaluationId' => $evaluationId]);
    }

    #[Route('/{evaluationId}/delete', name: 'app_evaluation_delete', methods: ['POST'])]
    public function delete(int $evaluationId): Response
    {
        $user = $this->requireCurrentUser();
        $evaluation = $this->entityManager->getRepository(Interview_evaluations::class)->find($evaluationId);

        if (!$evaluation) {
            throw $this->createNotFoundException('Evaluation not found');
        }

        // Authorization check - only recruiter owner or admin
        $roleName = strtolower($user->getRole()?->getName() ?? '');
        if ($roleName === 'recruiter' && $evaluation->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_evaluation_index');
        }

        $result = $this->evaluationService->deleteEvaluation($evaluation);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_evaluation_index');
    }

    #[Route('/{evaluationId}/export-pdf', name: 'app_evaluation_export_pdf', methods: ['GET'])]
    public function exportPdf(int $evaluationId): Response
    {
        $user = $this->requireCurrentUser();
        $evaluation = $this->entityManager->getRepository(Interview_evaluations::class)->find($evaluationId);

        if (!$evaluation) {
            throw $this->createNotFoundException('Evaluation not found');
        }

        $roleName = strtolower($user->getRole()?->getName() ?? '');
        if ($roleName === 'recruiter' && $evaluation->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_evaluation_index');
        }

        $scores = $this->entityManager->getConnection()->fetchAllAssociative(
            'SELECT es.score, es.comments, ec.criteria_name
             FROM evaluation_scores es
             LEFT JOIN evaluation_criteria ec ON es.criteria_id = ec.criteria_id
             WHERE es.evaluation_id = :evaluationId
             ORDER BY ec.display_order ASC',
            ['evaluationId' => $evaluationId]
        );

        $html = $this->renderView('recruiter/evaluation/pdf.html.twig', [
            'evaluation' => $evaluation,
            'scores' => $scores,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('evaluation_%d.pdf', $evaluation->getEvaluation_id());
        $response = new Response($dompdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename)
        );

        return $response;
    }

    private function requireCurrentUser(): Users
    {
        $user = $this->getUser();

        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException('You must be signed in to use this page.');
        }

        return $user;
    }
}
