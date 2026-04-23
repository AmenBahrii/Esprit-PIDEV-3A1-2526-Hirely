<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Interviews;
use App\Entity\Interview_types;
use App\Service\InterviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/interview')]
final class InterviewController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly InterviewService $interviewService,
    ) {}

    #[Route(name: 'app_interview_index', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName === 'admin') {
            $interviews = $this->entityManager->getConnection()->fetchAllAssociative(
                'SELECT i.*, a.*, it.type_name FROM interviews i
                 LEFT JOIN application a ON i.application_id = a.applicationId
                 LEFT JOIN interview_types it ON i.interview_type_id = it.interview_type_id
                 ORDER BY i.scheduled_date DESC'
            );
            return $this->render('admin/interview/index.html.twig', ['interviews' => $interviews]);
        }

        if ($roleName === 'recruiter') {
            $interviews = $this->interviewService->getRecruiterInterviews($user);
            return $this->render('recruiter/interview/index.html.twig', ['interviews' => $interviews]);
        }

        $interviews = [];
        return $this->render('candidate/interview/index.html.twig', ['interviews' => $interviews]);
    }

    #[Route('/new/{applicationId}', name: 'app_interview_new', methods: ['GET', 'POST'])]
    public function new(int $applicationId, Request $request): Response
    {
        $user = $this->getUser();
        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName !== 'recruiter' && $roleName !== 'admin') {
            return $this->redirectToRoute('app_interview_index');
        }

        $application = $this->entityManager->getRepository(Application::class)->find($applicationId);
        if (!$application) {
            throw $this->createNotFoundException('Application not found');
        }

        if ($request->isMethod('POST')) {
            $scheduledDate = new \DateTime($request->request->get('scheduled_date'));
            $result = $this->interviewService->scheduleInterview(
                $application,
                $user,
                (int)$request->request->get('interview_type_id'),
                $scheduledDate,
                $request->request->get('scheduled_time'),
                (int)$request->request->get('duration_minutes'),
                $request->request->get('location'),
                $request->request->get('meeting_link'),
                (int)$request->request->get('interview_round', 1)
            );

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_interview_show', ['interviewId' => $result['data']->getInterview_id()]);
            }

            $this->addFlash('error', $result['message']);
        }

        $interviewTypes = $this->entityManager->getRepository(Interview_types::class)->findAll();

        return $this->render('recruiter/interview/new.html.twig', [
            'application' => $application,
            'interview_types' => $interviewTypes,
        ]);
    }

    #[Route('/{interviewId}', name: 'app_interview_show', methods: ['GET'])]
    public function show(int $interviewId): Response
    {
        $user = $this->getUser();
        $interview = $this->entityManager->getRepository(Interviews::class)->find($interviewId);

        if (!$interview) {
            throw $this->createNotFoundException('Interview not found');
        }

        // Authorization check
        $roleName = strtolower($user->getRole()?->getName() ?? '');
        if ($roleName === 'recruiter' && $interview->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_interview_index');
        }

        return $this->render('recruiter/interview/show.html.twig', ['interview' => $interview]);
    }

    #[Route('/{interviewId}/edit', name: 'app_interview_edit', methods: ['GET', 'POST'])]
    public function edit(int $interviewId, Request $request): Response
    {
        $user = $this->getUser();
        $interview = $this->entityManager->getRepository(Interviews::class)->find($interviewId);

        if (!$interview) {
            throw $this->createNotFoundException('Interview not found');
        }

        // Authorization check
        if ($interview->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_interview_index');
        }

        if ($request->isMethod('POST')) {
            $scheduledDate = $request->request->get('scheduled_date') 
                ? new \DateTime($request->request->get('scheduled_date')) 
                : null;

            $result = $this->interviewService->updateInterview(
                $interview,
                $request->request->get('interview_type_id') ? (int)$request->request->get('interview_type_id') : null,
                $scheduledDate,
                $request->request->get('scheduled_time'),
                $request->request->get('duration_minutes') ? (int)$request->request->get('duration_minutes') : null,
                $request->request->get('location'),
                $request->request->get('meeting_link'),
                $request->request->get('notes')
            );

            if ($result['success']) {
                $this->addFlash('success', $result['message']);
                return $this->redirectToRoute('app_interview_show', ['interviewId' => $interviewId]);
            }

            $this->addFlash('error', $result['message']);
        }

        $interviewTypes = $this->entityManager->getRepository(Interview_types::class)->findAll();

        return $this->render('recruiter/interview/edit.html.twig', [
            'interview' => $interview,
            'interview_types' => $interviewTypes,
        ]);
    }

    #[Route('/{interviewId}/complete', name: 'app_interview_complete', methods: ['POST'])]
    public function complete(int $interviewId): Response
    {
        $user = $this->getUser();
        $interview = $this->entityManager->getRepository(Interviews::class)->find($interviewId);

        if (!$interview) {
            throw $this->createNotFoundException('Interview not found');
        }

        // Authorization check
        if ($interview->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_interview_index');
        }

        $result = $this->interviewService->completeInterview($interview);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_interview_show', ['interviewId' => $interviewId]);
    }

    #[Route('/{interviewId}/cancel', name: 'app_interview_cancel', methods: ['POST'])]
    public function cancel(int $interviewId): Response
    {
        $user = $this->getUser();
        $interview = $this->entityManager->getRepository(Interviews::class)->find($interviewId);

        if (!$interview) {
            throw $this->createNotFoundException('Interview not found');
        }

        // Authorization check
        if ($interview->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_interview_index');
        }

        $result = $this->interviewService->cancelInterview($interview);

        if ($result['success']) {
            $this->addFlash('success', $result['message']);
        } else {
            $this->addFlash('error', $result['message']);
        }

        return $this->redirectToRoute('app_interview_index');
    }

    #[Route('/{interviewId}/delete', name: 'app_interview_delete', methods: ['POST'])]
    public function delete(int $interviewId): Response
    {
        $user = $this->getUser();
        $interview = $this->entityManager->getRepository(Interviews::class)->find($interviewId);

        if (!$interview) {
            throw $this->createNotFoundException('Interview not found');
        }

        // Authorization check - only recruiter owner or admin
        $roleName = strtolower($user->getRole()?->getName() ?? '');
        if ($roleName === 'recruiter' && $interview->getRecruiter_id()->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_interview_index');
        }

        try {
            $this->entityManager->remove($interview);
            $this->entityManager->flush();
            $this->addFlash('success', 'Interview deleted successfully');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to delete interview: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_interview_index');
    }
}
