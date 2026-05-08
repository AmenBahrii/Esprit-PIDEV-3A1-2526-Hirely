<?php

namespace App\Controller;

use App\Entity\Application;
use App\Entity\Joboffer;
use App\Entity\Users;
use App\Form\ApplicationReviewType;
use App\Form\ApplicationType;
use App\Service\ApplicationReviewAssistantService;
use App\Service\ApplicationNotificationMailer;
use App\Service\ResumeAutofillService;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/application')]
final class ApplicationController extends AbstractController
{
    #[Route('/{applicationId}/accept', name: 'app_application_accept')]
    public function accept(Application $application, EntityManagerInterface $em, ApplicationNotificationMailer $notificationMailer): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        if (!$this->canRecruiterManageApplication($user, $application)) {
            return $this->redirectToRoute('app_application_index');
        }

        if (strtolower($application->getCurrentStatus()) !== 'pending') {
            return $this->redirectToRoute('app_application_index');
        }

        $application->setCurrentStatus('Accepted');
        $application->setLastUpdateDate(new \DateTime());
        $em->flush();
        $notificationMailer->sendDecisionToCandidate($application);

        $this->addFlash('success', 'Application accepted');

        return $this->redirectToRoute('app_application_index');
    }

    #[Route('/{applicationId}/reject', name: 'app_application_reject')]
    public function reject(Application $application, EntityManagerInterface $em, ApplicationNotificationMailer $notificationMailer): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        if (!$this->canRecruiterManageApplication($user, $application)) {
            return $this->redirectToRoute('app_application_index');
        }

        $application->setCurrentStatus('Rejected');
        $application->setLastUpdateDate(new \DateTime());
        $em->flush();
        $notificationMailer->sendDecisionToCandidate($application);

        $this->addFlash('error', 'Application rejected');

        return $this->redirectToRoute('app_application_index');
    }

    #[Route(name: 'app_application_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $roleName = strtolower($user->getRole()?->getName() ?? '');
        $connection = $em->getConnection();

        if ($roleName === 'admin') {
            $applications = $connection->fetchAllAssociative(
                'SELECT
                    a.applicationId,
                    a.applicationDate,
                    a.email,
                    a.phone,
                    a.currentStatus,
                    a.expectedSalary,
                    a.experienceYears,
                    a.score,
                    a.reviewNote,
                    a.jobOfferId,
                    j.title AS jobOfferTitle
                FROM `application` a
                LEFT JOIN joboffer j ON j.jobOfferId = a.jobOfferId
                ORDER BY a.applicationId DESC
                LIMIT 50'
            );

            return $this->render('admin/application/index.html.twig', [
                'applications' => $applications,
            ]);
        }

        if ($roleName === 'recruiter') {
            $applications = $connection->fetchAllAssociative(
                'SELECT
                    a.applicationId,
                    a.applicationDate,
                    a.email,
                    a.phone,
                    a.currentStatus,
                    a.expectedSalary,
                    a.experienceYears,
                    a.score,
                    a.reviewNote,
                    a.jobOfferId,
                    j.title AS jobOfferTitle
                FROM `application` a
                LEFT JOIN joboffer j ON j.jobOfferId = a.jobOfferId
                WHERE j.user_id = :userId
                ORDER BY a.applicationId DESC
                LIMIT 50',
                ['userId' => $user->getId()]
            );

            return $this->render('recruiter/application/index.html.twig', [
                'applications' => $applications,
            ]);
        }

        $applications = $connection->fetchAllAssociative(
            'SELECT
                a.applicationId,
                a.applicationDate,
                a.email,
                a.phone,
                a.currentStatus,
                a.expectedSalary,
                a.experienceYears,
                a.score,
                a.reviewNote,
                a.jobOfferId,
                j.title AS jobOfferTitle
            FROM `application` a
            LEFT JOIN joboffer j ON j.jobOfferId = a.jobOfferId
            WHERE a.user_id = :userId
            ORDER BY a.applicationId DESC
            LIMIT 50',
            ['userId' => $user->getId()]
        );

        return $this->render('candidate/application/index.html.twig', [
            'applications' => $applications,
        ]);
    }

    #[Route('/new/{jobOfferId}', name: 'app_application_new')]
    public function new(int $jobOfferId, Request $request, EntityManagerInterface $em, ApplicationNotificationMailer $notificationMailer): Response
    {
        if (!$this->isGranted('ROLE_CANDIDATE')) {
            throw $this->createAccessDeniedException();
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $jobOffer = $em->getRepository(Joboffer::class)->find($jobOfferId);

        if (!$jobOffer) {
            throw $this->createNotFoundException('Job offer not found');
        }

        if ($jobOffer->getStatus() !== 'Open') {
            $this->addFlash('error', 'This job is closed!');
            return $this->redirectToRoute('app_joboffer_index');
        }

        $existing = $em->getRepository(Application::class)->findOneBy([
            'user' => $user,
            'jobOffer' => $jobOffer,
        ]);

        if ($existing) {
            $this->addFlash('error', 'Cannot apply to the same offer twice.');
            return $this->redirectToRoute('app_joboffer_index');
        }

        $application = new Application();
        $application->setUser($user);
        $application->setJobOffer($jobOffer);
        $application->setEmail($user->getEmail());
        $application->setApplicationDate(new \DateTime());
        $application->setCurrentStatus('pending');
        $application->setLastUpdateDate(new \DateTime());
        $form = $this->createForm(ApplicationType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $application->setUser($user);
            $application->setJobOffer($jobOffer);
            $application->setApplicationDate(new \DateTime());
            $application->setCurrentStatus('pending');
            $application->setLastUpdateDate(new \DateTime());

            $em->persist($application);
            $em->flush();
            $notificationMailer->sendApplicationSubmittedToRecruiter($application);

            $this->addFlash('success', 'Application submitted successfully!');

            return $this->redirectToRoute('app_application_index');
        }

        return $this->render('candidate/application/new.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/new/{jobOfferId}/resume-autofill', name: 'app_application_resume_autofill', methods: ['POST'])]
    public function resumeAutofill(
        int $jobOfferId,
        Request $request,
        EntityManagerInterface $em,
        ResumeAutofillService $resumeAutofillService
    ): JsonResponse {
        if (!$this->isGranted('ROLE_CANDIDATE')) {
            return $this->json([
                'success' => false,
                'message' => 'Only candidates can use resume autofill.',
            ], Response::HTTP_FORBIDDEN);
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->json([
                'success' => false,
                'message' => 'Please sign in again before using resume autofill.',
            ], Response::HTTP_UNAUTHORIZED);
        }
        $user = $currentUser;

        $jobOffer = $em->getRepository(Joboffer::class)->find($jobOfferId);

        if (!$jobOffer) {
            return $this->json([
                'success' => false,
                'message' => 'Job offer not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $resume = $request->files->get('resume');

        if (!$resume instanceof UploadedFile) {
            return $this->json([
                'success' => false,
                'message' => 'Choose a resume file first.',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $fields = $resumeAutofillService->extractApplicationDraft($resume, $user, $jobOffer);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'We could not extract data from that resume right now. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'message' => 'We filled what we could from the resume. Please review everything before submitting.',
            'fields' => $fields,
        ]);
    }

    #[Route('/{applicationId}', name: 'app_application_show', methods: ['GET'])]
    public function show(Application $application): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $roleName = strtolower($user->getRole()?->getName() ?? '');

        if ($roleName === 'admin') {
            return $this->render('admin/application/show.html.twig', [
                'application' => $application,
            ]);
        }

        if ($roleName === 'recruiter') {
            if (!$this->canRecruiterManageApplication($user, $application)) {
                return $this->redirectToRoute('app_application_index');
            }

            $reviewForm = $this->createForm(ApplicationReviewType::class, $application, [
                'action' => $this->generateUrl('app_application_review', [
                    'applicationId' => $application->getApplicationId(),
                ]),
                'method' => 'POST',
            ]);

            return $this->render('recruiter/application/show.html.twig', [
                'application' => $application,
                'review_form' => $reviewForm,
            ]);
        }

        if ($application->getUser()?->getId() !== $user->getId()) {
            return $this->redirectToRoute('app_application_index');
        }

        return $this->render('candidate/application/show.html.twig', [
            'application' => $application,
        ]);
    }

    #[Route('/{applicationId}/edit', name: 'app_application_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(ApplicationType::class, $application, [
            'admin_mode' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $application->setLastUpdateDate(new \DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/application/edit.html.twig', [
            'application' => $application,
            'form' => $form,
        ]);
    }

    #[Route('/{applicationId}', name: 'app_application_delete', methods: ['POST'])]
    public function delete(Request $request, Application $application, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        $isAdmin = $this->isGranted('ROLE_ADMIN');
        $canCandidateDelete = $this->canCandidateDeleteApplication($user, $application);

        if (!$isAdmin && !$this->canRecruiterManageApplication($user, $application) && !$canCandidateDelete) {
            return $this->redirectToRoute('app_application_index');
        }

        if ($this->isCsrfTokenValid('delete'.$application->getApplicationId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($application);
            $entityManager->flush();

            $this->addFlash('success', 'Application deleted successfully.');
        }

        return $this->redirectToRoute('app_application_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{applicationId}/review', name: 'app_application_review', methods: ['POST'])]
    public function review(Request $request, Application $application, EntityManagerInterface $em): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->redirectToRoute('app_login');
        }
        $user = $currentUser;

        if (!$this->canRecruiterManageApplication($user, $application)) {
            return $this->redirectToRoute('app_application_index');
        }

        $form = $this->createForm(ApplicationReviewType::class, $application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $application->setLastUpdateDate(new \DateTime());
            $em->flush();

            $this->addFlash('success', 'Review updated successfully.');

            return $this->redirectToRoute('app_application_show', [
                'applicationId' => $application->getApplicationId(),
            ]);
        }

        return $this->render('recruiter/application/show.html.twig', [
            'application' => $application,
            'review_form' => $form,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[Route('/{applicationId}/ai-review', name: 'app_application_ai_review', methods: ['POST'])]
    public function aiReview(
        Application $application,
        ApplicationReviewAssistantService $reviewAssistantService
    ): JsonResponse {
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Users) {
            return $this->json([
                'success' => false,
                'message' => 'Please sign in again before using AI review.',
            ], Response::HTTP_UNAUTHORIZED);
        }
        $user = $currentUser;

        if (!$this->canRecruiterManageApplication($user, $application)) {
            return $this->json([
                'success' => false,
                'message' => 'Only the recruiter who owns this job offer can use AI review.',
            ], Response::HTTP_FORBIDDEN);
        }

        try {
            $review = $reviewAssistantService->generateReview($application);
        } catch (\RuntimeException $exception) {
            return $this->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'We could not generate the AI review right now. Please try again.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json([
            'success' => true,
            'message' => 'AI review drafted a score and note. Please check them before saving.',
            'review' => $review,
        ]);
    }

    private function canRecruiterManageApplication(Users $user, Application $application): bool
    {
        try {
            return strtolower($user->getRole()?->getName() ?? '') === 'recruiter'
                && $application->getJobOffer()
                && $application->getJobOffer()->getUser() === $user;
        } catch (EntityNotFoundException) {
            return false;
        }
    }

    private function canCandidateDeleteApplication(Users $user, Application $application): bool
    {
        return strtolower($user->getRole()?->getName() ?? '') === 'candidate'
            && $application->getUser()?->getId() === $user->getId()
            && strtolower($application->getCurrentStatus()) === 'pending';
    }
}
