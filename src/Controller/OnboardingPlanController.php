<?php

namespace App\Controller;

use App\Entity\Onboardingplan;
use App\Form\OnboardingPlanType;
use App\Onboarding\OnboardingPlanStatusManager;
use App\Onboarding\PublicUrlConfiguration;
use App\Onboarding\ViewerContext;
use App\Repository\OnboardingplanRepository;
use App\Repository\OnboardingtaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OnboardingPlanController extends AbstractController
{
    #[Route('/admin/plans', name: 'app_admin_plans')]
    #[Route('/workspace/plans', name: 'app_workspace_plans')]
    public function index(Request $request, OnboardingplanRepository $planRepository, OnboardingtaskRepository $taskRepository, ViewerContext $viewerContext, PublicUrlConfiguration $publicUrlConfiguration, OnboardingPlanStatusManager $onboardingPlanStatusManager, EntityManagerInterface $entityManager): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        $viewer = $viewerContext->getCurrentUser();
        $searchTerm = trim((string) $request->query->get('q', ''));
        $caseSensitive = $request->query->getBoolean('case_sensitive');
        $filters = [
            'status' => trim((string) $request->query->get('status', '')),
            'sort' => trim((string) $request->query->get('sort', 'newest')),
            'overdue_only' => $request->query->getBoolean('overdue_only'),
        ];
        $plans = $viewer ? $planRepository->findVisibleFor($viewer, $searchTerm, $caseSensitive, $filters) : [];
        $plansWereSynced = false;
        $statusSummaryByPlanId = $taskRepository->getStatusSummaryForPlanIds(array_map(
            static fn (Onboardingplan $plan): int => (int) $plan->getPlanId(),
            $plans
        ));

        foreach ($plans as $plan) {
            $planId = (int) $plan->getPlanId();
            $plansWereSynced = $onboardingPlanStatusManager->syncPlanStatusFromSummary($plan, $statusSummaryByPlanId[$planId] ?? []) || $plansWereSynced;
        }

        if ($plansWereSynced) {
            $entityManager->flush();
        }

        $viewData = [
            'plans' => $plans,
            'search_term' => $searchTerm,
            'case_sensitive' => $caseSensitive,
            'selected_status' => $filters['status'],
            'selected_sort' => $filters['sort'],
            'overdue_only' => $filters['overdue_only'],
            'plan_metrics' => $this->buildPlanMetrics($plans),
            'plan_status_choices' => Onboardingplan::getStatusChoices(),
            'public_qr_base_url' => $publicUrlConfiguration->resolveBaseUrl($request),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/plans/_results.html.twig', $viewData);
        }

        return $this->render('admin/plans/index.html.twig', $viewData);
    }

    #[Route('/admin/plans/new', name: 'app_admin_plans_new')]
    #[Route('/workspace/plans/new', name: 'app_workspace_plans_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canCreatePlans()) {
            $this->addFlash('error', 'Candidates cannot create onboarding plans.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        $plan = new Onboardingplan();
        $form = $this->createForm(OnboardingPlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($plan);
            $plan->setStatus(Onboardingplan::STATUS_PENDING);
            if (!$plan->getQrToken()) {
                $plan->setQrToken($this->generateQrToken());
            }
            $entityManager->flush();

            return $this->redirectToRoute($this->plansRoute($request));
        }

        return $this->render('admin/plans/form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Add Onboarding Plan',
            'limited_editor' => false,
            'plan' => $plan,
        ]);
    }

    #[Route('/admin/plans/{id}/edit', name: 'app_admin_plans_edit')]
    #[Route('/workspace/plans/{id}/edit', name: 'app_workspace_plans_edit')]
    public function edit(Onboardingplan $plan, Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext, OnboardingPlanStatusManager $onboardingPlanStatusManager): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canEditPlan($plan)) {
            $this->addFlash('error', 'You are not allowed to update this onboarding plan.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        $form = $this->createForm(OnboardingPlanType::class, $plan);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $onboardingPlanStatusManager->syncPlanStatus($plan);
            if (!$plan->getQrToken()) {
                $plan->setQrToken($this->generateQrToken());
            }
            $entityManager->flush();

            return $this->redirectToRoute($this->plansRoute($request));
        }

        return $this->render('admin/plans/form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Edit Onboarding Plan',
            'limited_editor' => false,
            'plan' => $plan,
        ]);
    }

    #[Route('/admin/plans/{id}/delete', name: 'app_admin_plans_delete', methods: ['POST'])]
    #[Route('/workspace/plans/{id}/delete', name: 'app_workspace_plans_delete', methods: ['POST'])]
    public function delete(Onboardingplan $plan, Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canDeletePlan($plan)) {
            $this->addFlash('error', 'You are not allowed to delete this onboarding plan.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        if ($this->isCsrfTokenValid('delete_plan_' . $plan->getPlanId(), $request->request->get('_token'))) {
            $entityManager->remove($plan);
            $entityManager->flush();
        }

        return $this->redirectToRoute($this->plansRoute($request));
    }

    #[Route('/admin/plans/{id}/qr', name: 'app_admin_plans_qr')]
    #[Route('/workspace/plans/{id}/qr', name: 'app_workspace_plans_qr')]
    public function qr(Onboardingplan $plan, Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext, PublicUrlConfiguration $publicUrlConfiguration, OnboardingPlanStatusManager $onboardingPlanStatusManager, OnboardingtaskRepository $taskRepository): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canViewQr($plan)) {
            $this->addFlash('error', 'You are not allowed to view this QR code.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        $qrTokenWasGenerated = false;

        if (!$plan->getQrToken()) {
            $plan->setQrToken($this->generateQrToken());
            $qrTokenWasGenerated = true;
        }

        $taskSummary = $taskRepository->getStatusSummaryForPlanIds([(int) $plan->getPlanId()]);

        if ($onboardingPlanStatusManager->syncPlanStatusFromSummary($plan, $taskSummary[(int) $plan->getPlanId()] ?? []) || $qrTokenWasGenerated) {
            $entityManager->flush();
        }

        return $this->render('admin/plans/qr.html.twig', [
            'plan' => $plan,
            'public_qr_base_url' => $publicUrlConfiguration->resolveBaseUrl($request),
        ]);
    }

    #[Route('/qr/{token}', name: 'app_public_plan_qr')]
    public function publicQr(string $token, OnboardingplanRepository $planRepository, OnboardingtaskRepository $taskRepository, OnboardingPlanStatusManager $onboardingPlanStatusManager, EntityManagerInterface $entityManager): Response
    {
        $plan = $planRepository->findOneByQrToken($token);
        if (!$plan) {
            throw new NotFoundHttpException('No onboarding plan matches this QR code.');
        }

        $taskSummary = $taskRepository->getStatusSummaryForPlanIds([(int) $plan->getPlanId()]);

        if ($onboardingPlanStatusManager->syncPlanStatusFromSummary($plan, $taskSummary[(int) $plan->getPlanId()] ?? [])) {
            $entityManager->flush();
        }

        return $this->render('qr/public_plan.html.twig', [
            'plan' => $plan,
            'tasks' => $taskRepository->findByPlan($plan),
        ]);
    }

    private function generateQrToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(24)), '+/', '-_'), '=');
    }

    private function redirectForArea(Request $request, ViewerContext $viewerContext): ?RedirectResponse
    {
        if ($this->isAdminArea($request) && !$viewerContext->isAdmin()) {
            $this->addFlash('error', 'The admin backend is reserved for admin accounts.');

            return $this->redirectToRoute('app_workspace');
        }

        if (!$this->isAdminArea($request) && $viewerContext->isAdmin()) {
            return $this->redirectToRoute('app_admin');
        }

        return null;
    }

    private function isAdminArea(Request $request): bool
    {
        return str_starts_with((string) $request->attributes->get('_route'), 'app_admin');
    }

    private function plansRoute(Request $request): string
    {
        return $this->isAdminArea($request) ? 'app_admin_plans' : 'app_workspace_plans';
    }

    /**
     * @param Onboardingplan[] $plans
     * @return array{total: int, overdue: int, completed: int, active: int}
     */
    private function buildPlanMetrics(array $plans): array
    {
        $overdue = 0;
        $completed = 0;
        $active = 0;

        foreach ($plans as $plan) {
            if ($plan->isOverdue()) {
                ++$overdue;
            }

            if ($plan->isCompleted()) {
                ++$completed;
            }

            if (Onboardingplan::STATUS_IN_PROGRESS === $plan->getStatus()) {
                ++$active;
            }
        }

        return [
            'total' => \count($plans),
            'overdue' => $overdue,
            'completed' => $completed,
            'active' => $active,
        ];
    }
}
