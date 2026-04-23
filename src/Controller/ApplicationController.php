<?php

namespace App\Controller;

<<<<<<< HEAD
use App\Service\DatabaseService;
use App\Service\TemplateRenderer;
=======
use App\Entity\Application;
use App\Entity\Joboffer;
use App\Entity\User;
use App\Form\ApplicationReviewType;
use App\Form\ApplicationType;
use App\Service\ApplicationNotificationMailer;
use App\Service\ApplicationReviewAssistantService;
use App\Service\ResumeAutofillService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
>>>>>>> OnboardingCoordination
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

<<<<<<< HEAD
#[Route('/applications')]
class ApplicationController
{
    #[Route('', name: 'app_applications')]
    public function list(): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();
        
        $applications = $db->getApplications();
        
        $html = $renderer->render('application/list.html.twig', [
            'applications' => $applications,
        ]);
        
        return new Response($html);
    }

    #[Route('/new', name: 'app_application_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        if ($request->isMethod('POST')) {
            $data = [
                'candidate_name' => $request->request->get('candidate_name'),
                'job_id' => $request->request->get('job_id'),
                'status' => $request->request->get('status', 'applied'),
            ];

            try {
                $db->createApplication($data);
                return new Response('<script>window.location.href = "/applications"; alert("Application created successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error: ' . $e->getMessage(), 500);
            }
        }

        $html = $renderer->render('application/form.html.twig', [
            'application' => null,
        ]);

        return new Response($html);
    }

    #[Route('/{id}', name: 'app_application_show')]
    public function show(int $id): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $application = $db->getApplicationById($id);
        if (!$application) {
            return new Response('Application not found', 404);
        }

        // Get related interviews for this application
        $stmt = $db->getPDO()->prepare("
            SELECT i.id, i.schedule_date, i.format, i.status,
                   it.name as interview_type_name
            FROM interview i
            LEFT JOIN interview_type it ON i.interview_type_id = it.id
            WHERE i.application_id = :id
            ORDER BY i.schedule_date DESC
        ");
        $stmt->execute([':id' => $id]);
        $interviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $html = $renderer->render('application/show.html.twig', [
            'application' => $application,
            'interviews' => $interviews,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/edit', name: 'app_application_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $application = $db->getApplicationById($id);
        if (!$application) {
            return new Response('Application not found', 404);
        }

        if ($request->isMethod('POST')) {
            $data = [
                'candidate_name' => $request->request->get('candidate_name'),
                'job_id' => $request->request->get('job_id'),
                'status' => $request->request->get('status', 'applied'),
            ];

            try {
                $db->updateApplication($id, $data);
                return new Response('<script>window.location.href = "/applications"; alert("Application updated successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error: ' . $e->getMessage(), 500);
            }
        }

        $html = $renderer->render('application/form.html.twig', [
            'application' => $application,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/delete', name: 'app_application_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $db = new DatabaseService();

        $application = $db->getApplicationById($id);
        if (!$application) {
            return new Response('Application not found', 404);
        }

        try {
            $db->deleteApplication($id);
            return new Response('<script>window.location.href = "/applications"; alert("Application deleted successfully!");</script>');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
=======
final class ApplicationController extends AbstractController
{
    #[Route('/admin/applications', name: 'app_admin_application_index', methods: ['GET'])]
    public function adminIndex(EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->renderApplicationIndex($entityManager, $user, true);
    }

    #[Route('/workspace/applications', name: 'app_workspace_application_index', methods: ['GET'])]
    public function workspaceIndex(EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$this->isGranted('ROLE_RECRUITER') && !$this->isGranted('ROLE_CANDIDATE')) {
            return $this->redirectToRoute('app_home');
        }

        return $this->renderApplicationIndex($entityManager, $user, false);
    }

    #[Route('/workspace/applications/new/{jobOfferId}', name: 'app_workspace_application_new', methods: ['GET', 'POST'])]
    public function workspaceNew(
        int $jobOfferId,
        Request $request,
        EntityManagerInterface $entityManager,
        ApplicationNotificationMailer $notificationMailer
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_CANDIDATE');

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $jobOffer = $entityManager->getRepository(Joboffer::class)->find($jobOfferId);
        if (!$jobOffer) {
            throw $this->createNotFoundException('Job offer not found.');
        }

        if ('Open' !== $jobOffer->getStatus()) {
            $this->addFlash('error', 'This job offer is closed.');

            return $this->redirectToRoute('app_workspace_joboffer_index');
        }

        $existing = $entityManager->getRepository(Application::class)->findOneBy([
            'user' => $user,
            'joboffer' => $jobOffer,
        ]);

        if ($existing) {
            $this->addFlash('error', 'You already applied to this job offer.');

            return $this->redirectToRoute('app_workspace_application_index');
        }

        $application = new Application();
        $application->setUser($user);
        $application->setJobOffer($jobOffer);
        $application->setEmail($user->getEmail());
        $application->setApplicationDate(new \DateTime());
        $application->setCurrentStatus('Pending');
        $application->setLastUpdateDate(new \DateTime());

        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resumeFile = $form->get('resumeFile')->getData();
            if ($resumeFile instanceof UploadedFile) {
                $application->setResumePath($this->storeResume($resumeFile));
            }

            $application->setApplicationDate(new \DateTime());
            $application->setLastUpdateDate(new \DateTime());

            $entityManager->persist($application);
            $entityManager->flush();

            $notificationMailer->sendApplicationSubmittedToRecruiter($application);

            $this->addFlash('success', 'Application submitted successfully.');

            return $this->redirectToRoute('app_workspace_application_index');
        }

        return $this->renderApplicationPage('application/new.html.twig', [
            'form' => $form->createView(),
            'application' => $application,
            'joboffer' => $jobOffer,
            'topbar_title' => 'Submit Application',
        ]);
    }

    #[Route('/workspace/applications/new/{jobOfferId}/resume-autofill', name: 'app_workspace_application_resume_autofill', methods: ['POST'])]
    public function workspaceResumeAutofill(
        int $jobOfferId,
        Request $request,
        EntityManagerInterface $entityManager,
        ResumeAutofillService $resumeAutofillService
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_CANDIDATE');

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Please sign in again first.'], Response::HTTP_UNAUTHORIZED);
        }

        $jobOffer = $entityManager->getRepository(Joboffer::class)->find($jobOfferId);
        if (!$jobOffer) {
            return $this->json(['success' => false, 'message' => 'Job offer not found.'], Response::HTTP_NOT_FOUND);
        }

        $resume = $request->files->get('resume');
        if (!$resume instanceof UploadedFile) {
            return $this->json(['success' => false, 'message' => 'Choose a resume file first.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $fields = $resumeAutofillService->extractApplicationDraft($resume, $user, $jobOffer);
        } catch (\RuntimeException $exception) {
            return $this->json(['success' => false, 'message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return $this->json(['success' => false, 'message' => 'Resume autofill is unavailable right now.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'message' => 'We filled what we could from the resume. Please review the form before submitting.',
            'fields' => $fields,
        ]);
    }

    #[Route('/admin/applications/{id}', name: 'app_admin_application_show', methods: ['GET'])]
    public function adminShow(Application $application): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->renderApplicationPage('admin/application/show.html.twig', [
            'application' => $application,
            'role_name' => 'admin',
            'review_form' => null,
            'topbar_title' => 'Application Details',
        ]);
    }

    #[Route('/workspace/applications/{id}', name: 'app_workspace_application_show', methods: ['GET'])]
    public function workspaceShow(Application $application): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $roleName = strtolower((string) $user->getRole()?->getName());

        if ('candidate' === $roleName && $application->getUser()?->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_workspace_application_index');
        }

        if ('recruiter' === $roleName && !$this->canRecruiterManageApplication($user, $application)) {
            return $this->redirectToRoute('app_workspace_application_index');
        }

        $reviewForm = null;
        if ('recruiter' === $roleName) {
            $reviewForm = $this->createForm(ApplicationReviewType::class, $application, [
                'action' => $this->generateUrl('app_workspace_application_review', ['id' => $application->getId()]),
                'method' => 'POST',
            ]);
        }

        return $this->renderApplicationPage('application/show.html.twig', [
            'application' => $application,
            'role_name' => $roleName,
            'review_form' => $reviewForm?->createView(),
            'topbar_title' => 'Application Details',
        ]);
    }

    #[Route('/admin/applications/{id}/edit', name: 'app_admin_application_edit', methods: ['GET', 'POST'])]
    public function adminEdit(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ApplicationType::class, $application, [
            'admin_mode' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resumeFile = $form->get('resumeFile')->getData();
            if ($resumeFile instanceof UploadedFile) {
                $application->setResumePath($this->storeResume($resumeFile));
            }

            $application->setLastUpdateDate(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Application updated successfully.');

            return $this->redirectToRoute('app_admin_application_index');
        }

        return $this->renderApplicationPage('admin/application/edit.html.twig', [
            'form' => $form->createView(),
            'application' => $application,
            'topbar_title' => 'Edit Application',
        ]);
    }

    #[Route('/admin/applications/{id}', name: 'app_admin_application_delete', methods: ['POST'])]
    public function adminDelete(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete' . $application->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($application);
            $entityManager->flush();
            $this->addFlash('success', 'Application deleted successfully.');
        }

        return $this->redirectToRoute('app_admin_application_index');
    }

    #[Route('/workspace/applications/{id}', name: 'app_workspace_application_delete', methods: ['POST'])]
    public function workspaceDelete(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $canDelete = $this->canRecruiterManageApplication($user, $application)
            || $this->canCandidateDeleteApplication($user, $application);

        if (!$canDelete) {
            return $this->redirectToRoute('app_workspace_application_index');
        }

        if ($this->isCsrfTokenValid('delete' . $application->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($application);
            $entityManager->flush();
            $this->addFlash('success', 'Application deleted successfully.');
        }

        return $this->redirectToRoute('app_workspace_application_index');
    }

    #[Route('/workspace/applications/{id}/accept', name: 'app_workspace_application_accept', methods: ['POST'])]
    public function workspaceAccept(
        Request $request,
        Application $application,
        EntityManagerInterface $entityManager,
        ApplicationNotificationMailer $notificationMailer
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (
            !$user
            || !$this->canRecruiterManageApplication($user, $application)
            || !$this->isCsrfTokenValid('application_accept_' . $application->getId(), (string) $request->request->get('_token'))
        ) {
            return $this->redirectToRoute('app_workspace_application_index');
        }

        if ('pending' === strtolower((string) $application->getCurrentStatus())) {
            $application->setCurrentStatus('Accepted');
            $application->setLastUpdateDate(new \DateTime());
            $entityManager->flush();

            $notificationMailer->sendDecisionToCandidate($application);

            $this->addFlash('success', 'Application accepted.');
        }

        return $this->redirectToRoute('app_workspace_application_show', ['id' => $application->getId()]);
    }

    #[Route('/workspace/applications/{id}/reject', name: 'app_workspace_application_reject', methods: ['POST'])]
    public function workspaceReject(
        Request $request,
        Application $application,
        EntityManagerInterface $entityManager,
        ApplicationNotificationMailer $notificationMailer
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();
        if (
            !$user
            || !$this->canRecruiterManageApplication($user, $application)
            || !$this->isCsrfTokenValid('application_reject_' . $application->getId(), (string) $request->request->get('_token'))
        ) {
            return $this->redirectToRoute('app_workspace_application_index');
        }

        $application->setCurrentStatus('Rejected');
        $application->setLastUpdateDate(new \DateTime());
        $entityManager->flush();

        $notificationMailer->sendDecisionToCandidate($application);

        $this->addFlash('error', 'Application rejected.');

        return $this->redirectToRoute('app_workspace_application_show', ['id' => $application->getId()]);
    }

    #[Route('/workspace/applications/{id}/review', name: 'app_workspace_application_review', methods: ['POST'])]
    public function workspaceReview(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user || !$this->canRecruiterManageApplication($user, $application)) {
            return $this->redirectToRoute('app_workspace_application_index');
        }

        $form = $this->createForm(ApplicationReviewType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!\in_array((string) $application->getCurrentStatus(), ['Accepted', 'Rejected'], true)) {
                $application->setCurrentStatus('Reviewed');
            }

            $application->setLastUpdateDate(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Review updated successfully.');

            return $this->redirectToRoute('app_workspace_application_show', ['id' => $application->getId()]);
        }

        return $this->renderApplicationPage(
            'application/show.html.twig',
            [
                'application' => $application,
                'role_name' => 'recruiter',
                'review_form' => $form->createView(),
                'topbar_title' => 'Application Details',
            ],
            new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

    #[Route('/workspace/applications/{id}/ai-review', name: 'app_workspace_application_ai_review', methods: ['POST'])]
    public function workspaceAiReview(
        Application $application,
        ApplicationReviewAssistantService $reviewAssistantService
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Please sign in again first.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->canRecruiterManageApplication($user, $application)) {
            return $this->json(['success' => false, 'message' => 'Only the recruiter who owns this job offer can use AI review.'], Response::HTTP_FORBIDDEN);
        }

        try {
            $review = $reviewAssistantService->generateReview($application);
        } catch (\RuntimeException $exception) {
            return $this->json(['success' => false, 'message' => $exception->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return $this->json(['success' => false, 'message' => 'AI review is unavailable right now.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'message' => 'AI review drafted a score and a recruiter note.',
            'review' => $review,
        ]);
    }

    private function renderApplicationIndex(
        EntityManagerInterface $entityManager,
        User $user,
        bool $adminArea
    ): Response {
        $roleName = strtolower((string) $user->getRole()?->getName());

        $qb = $entityManager->getRepository(Application::class)->createQueryBuilder('a')
            ->leftJoin('a.joboffer', 'j')->addSelect('j')
            ->leftJoin('a.user', 'u')->addSelect('u')
            ->orderBy('a.applicationId', 'DESC');

        if ($adminArea) {
            // admin sees all applications
        } elseif ('recruiter' === $roleName) {
            $qb->andWhere('j.user = :user')->setParameter('user', $user);
        } else {
            $qb->andWhere('a.user = :user')->setParameter('user', $user);
        }

        return $this->renderApplicationPage(
            $adminArea ? 'admin/application/index.html.twig' : 'application/index.html.twig',
            [
                'applications' => $qb->getQuery()->getResult(),
                'role_name' => $roleName,
                'topbar_title' => 'Applications',
            ]
        );
    }

    private function renderApplicationPage(
        string $template,
        array $data = [],
        ?Response $response = null
    ): Response {
        return $this->render(
            $template,
            array_merge(
                [
                    'topbar_subtitle' => 'Recruitment and onboarding workspace',
                ],
                $data
            ),
            $response
        );
    }

    private function canRecruiterManageApplication(User $user, Application $application): bool
    {
        return $this->isGranted('ROLE_RECRUITER')
            && $application->getJobOffer()
            && $application->getJobOffer()->getUser()?->getId() === $user->getId();
    }

    private function canCandidateDeleteApplication(User $user, Application $application): bool
    {
        return $this->isGranted('ROLE_CANDIDATE')
            && $application->getUser()?->getId() === $user->getId()
            && 'pending' === strtolower((string) $application->getCurrentStatus());
    }

    private function storeResume(UploadedFile $resumeFile): string
    {
        $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/resumes';

        if (!is_dir($uploadsDirectory)) {
            mkdir($uploadsDirectory, 0777, true);
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($resumeFile->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'resume';
        $extension = $resumeFile->guessExtension() ?: $resumeFile->getClientOriginalExtension() ?: 'bin';
        $filename = strtolower($safeName) . '-' . bin2hex(random_bytes(6)) . '.' . $extension;

        $resumeFile->move($uploadsDirectory, $filename);

        return $filename;
>>>>>>> OnboardingCoordination
    }
}
