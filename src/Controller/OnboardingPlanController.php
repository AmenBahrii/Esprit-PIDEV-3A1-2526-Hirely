<?php

namespace App\Controller;

use App\Entity\Onboardingplan;
use App\Form\OnboardingPlanTemplateAssignmentType;
use App\Form\OnboardingPlanType;
use App\Onboarding\OnboardingLanguageContext;
use App\Onboarding\OnboardingFlowPresenter;
use App\Onboarding\OnboardingFlowService;
use App\Onboarding\LibreTranslateService;
use App\Onboarding\OnboardingPlanTemplateCatalog;
use App\Onboarding\OnboardingPlanTemplateSelection;
use App\Onboarding\OnboardingPlanStatusManager;
use App\Onboarding\PublicUrlConfiguration;
use App\Onboarding\ViewerContext;
use App\Repository\OnboardingplanRepository;
use App\Repository\OnboardingtaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class OnboardingPlanController extends AbstractController
{
    #[Route('/admin/plans', name: 'app_admin_plans')]
    #[Route('/workspace/plans', name: 'app_workspace_plans')]
    public function index(Request $request, OnboardingplanRepository $planRepository, OnboardingtaskRepository $taskRepository, ViewerContext $viewerContext, PublicUrlConfiguration $publicUrlConfiguration, OnboardingPlanStatusManager $onboardingPlanStatusManager, EntityManagerInterface $entityManager, ChartBuilderInterface $chartBuilder, OnboardingLanguageContext $onboardingLanguageContext, OnboardingFlowService $onboardingFlowService, OnboardingFlowPresenter $onboardingFlowPresenter): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        $selectedLanguage = $onboardingLanguageContext->resolveFromRequest($request);
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
            'plan_flow_panel' => $this->buildPlanFlowPanel(
                $plans,
                $taskRepository->findGroupedByPlanIds(array_map(static fn (Onboardingplan $plan): int => (int) $plan->getPlanId(), $plans)),
                $onboardingFlowService,
                $onboardingFlowPresenter,
                $selectedLanguage
            ),
            'plan_status_chart' => $this->buildPlanStatusChart($chartBuilder, $plans, $onboardingLanguageContext, $selectedLanguage),
            'plan_status_choices' => Onboardingplan::getStatusChoices(),
            'public_qr_base_url' => $publicUrlConfiguration->resolveBaseUrl($request),
            'selected_language' => $selectedLanguage,
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

    #[Route('/admin/plans/template', name: 'app_admin_plans_template')]
    #[Route('/workspace/plans/template', name: 'app_workspace_plans_template')]
    public function fromTemplate(Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext, OnboardingPlanTemplateCatalog $templateCatalog, OnboardingPlanStatusManager $onboardingPlanStatusManager): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canCreatePlans()) {
            $this->addFlash('error', 'Candidates cannot create onboarding plans.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        $selection = new OnboardingPlanTemplateSelection();

        $form = $this->createForm(OnboardingPlanTemplateAssignmentType::class, $selection, [
            'template_choices' => $templateCatalog->choiceMap(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plan = $templateCatalog->createPlanFromTemplate(
                (string) $selection->getTemplateKey(),
                $selection->getUser(),
                $selection->getDeadline()
            );

            if (!$plan) {
                $this->addFlash('error', 'The selected onboarding template could not be found.');

                return $this->redirectToRoute($this->templateRoute($request));
            }

            $plan->setQrToken($this->generateQrToken());
            $onboardingPlanStatusManager->syncPlanStatus($plan);
            $entityManager->persist($plan);
            foreach ($plan->getOnboardingtasks() as $task) {
                $entityManager->persist($task);
            }
            $entityManager->flush();

            $selectedTemplate = $templateCatalog->find((string) $selection->getTemplateKey());
            if ($selectedTemplate) {
                $this->addFlash('success', sprintf('Plan created from the "%s" template.', $selectedTemplate['name']));
            }

            return $this->redirectToRoute($this->plansRoute($request));
        }

        return $this->render('admin/plans/template_form.html.twig', [
            'form' => $form->createView(),
            'page_title' => 'Create Plan From Template',
            'templates_catalog' => $templateCatalog->all(),
            'selected_template_key' => $selection->getTemplateKey(),
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
    public function publicQr(string $token, Request $request, OnboardingplanRepository $planRepository, OnboardingtaskRepository $taskRepository, OnboardingPlanStatusManager $onboardingPlanStatusManager, EntityManagerInterface $entityManager, LibreTranslateService $libreTranslateService, OnboardingLanguageContext $onboardingLanguageContext): Response
    {
        $plan = $planRepository->findOneByQrToken($token);
        if (!$plan) {
            throw new NotFoundHttpException('No onboarding plan matches this QR code.');
        }

        $taskSummary = $taskRepository->getStatusSummaryForPlanIds([(int) $plan->getPlanId()]);

        if ($onboardingPlanStatusManager->syncPlanStatusFromSummary($plan, $taskSummary[(int) $plan->getPlanId()] ?? [])) {
            $entityManager->flush();
        }

        $tasks = $taskRepository->findByPlan($plan);
        $selectedLanguage = $onboardingLanguageContext->resolveFromRequest($request);

        return $this->render('qr/public_plan.html.twig', [
            'plan' => $plan,
            'tasks' => $tasks,
            'selected_language' => $selectedLanguage,
            'translation_languages' => $libreTranslateService->getLanguageChoices(),
            'translation_enabled' => $libreTranslateService->isEnabled(),
            'qr_translation' => $this->buildQrTranslation($plan, $tasks, $libreTranslateService, $selectedLanguage),
        ]);
    }

    #[Route('/api/onboarding/plans/{id}/flow', name: 'app_api_onboarding_plan_flow', methods: ['GET'])]
    public function flowApi(Onboardingplan $plan, Request $request, ViewerContext $viewerContext, OnboardingtaskRepository $taskRepository, OnboardingFlowService $onboardingFlowService, OnboardingFlowPresenter $onboardingFlowPresenter, OnboardingLanguageContext $onboardingLanguageContext): JsonResponse
    {
        if (!$viewerContext->canViewPlan($plan)) {
            return $this->json(['error' => 'You are not allowed to view this onboarding flow.'], Response::HTTP_FORBIDDEN);
        }

        $selectedLanguage = $onboardingLanguageContext->resolveFromRequest($request);
        $flow = $onboardingFlowService->buildPlanFlow($plan, $taskRepository->findByPlan($plan));

        return $this->json([
            'flow' => $onboardingFlowPresenter->translateFlow($flow, $selectedLanguage),
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

    private function templateRoute(Request $request): string
    {
        return $this->isAdminArea($request) ? 'app_admin_plans_template' : 'app_workspace_plans_template';
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

    /**
     * @param Onboardingplan[] $plans
     * @param array<int, \App\Entity\Onboardingtask[]> $tasksByPlanId
     * @return array{
     *     average_progress: int,
     *     highest_risk: string,
     *     total_risk_signals: int,
     *     priority_plan_label: string,
     *     priority_phase: string,
     *     top_action: string,
     *     summary: string
     * }
     */
    private function buildPlanFlowPanel(
        array $plans,
        array $tasksByPlanId,
        OnboardingFlowService $onboardingFlowService,
        OnboardingFlowPresenter $onboardingFlowPresenter,
        string $selectedLanguage,
    ): array {
        if ([] === $plans) {
            return [
                'average_progress' => 0,
                'highest_risk' => 'low',
                'total_risk_signals' => 0,
                'priority_plan_label' => 'No visible plan',
                'priority_phase' => 'Pre-arrival',
                'top_action' => 'No action needed right now.',
                'summary' => 'The flow panel will react once onboarding plans are available in this view.',
            ];
        }

        $priorityFlow = null;
        $priorityPlan = null;
        $totalProgress = 0;
        $totalRiskSignals = 0;
        $highestRisk = 'low';

        foreach ($plans as $plan) {
            $flow = $onboardingFlowService->buildPlanFlow($plan, $tasksByPlanId[(int) $plan->getPlanId()] ?? []);
            $totalProgress += $flow['progressPercent'];
            $totalRiskSignals += \count($flow['riskSignals']);
            $highestRisk = $this->highestRiskLevel($highestRisk, $flow['riskLevel']);

            if (null === $priorityFlow || $this->flowPriorityScore($flow) > $this->flowPriorityScore($priorityFlow)) {
                $priorityFlow = $flow;
                $priorityPlan = $plan;
            }
        }

        $translatedPriorityFlow = $priorityFlow
            ? $onboardingFlowPresenter->translateFlow($priorityFlow, $selectedLanguage)
            : null;

        return [
            'average_progress' => (int) round($totalProgress / max(1, \count($plans))),
            'highest_risk' => $highestRisk,
            'total_risk_signals' => $totalRiskSignals,
            'priority_plan_label' => $priorityPlan
                ? trim((string) $priorityPlan->getUser()?->getFirstName() . ' ' . (string) $priorityPlan->getUser()?->getLastName()) . sprintf(' (Plan #%d)', (int) $priorityPlan->getPlanId())
                : 'No visible plan',
            'priority_phase' => $translatedPriorityFlow['currentPhase']['phaseLabel'] ?? 'Pre-arrival',
            'top_action' => $translatedPriorityFlow['nextActions'][0]['title'] ?? 'No action needed right now.',
            'summary' => $translatedPriorityFlow['smartSummary'] ?? 'The flow panel will react once onboarding plans are available in this view.',
        ];
    }

    /**
     * @param array{riskLevel: string, riskSignals: array, nextActions: array, currentPhase: array{status: string}} $flow
     */
    private function flowPriorityScore(array $flow): int
    {
        $riskScore = match ($flow['riskLevel']) {
            'high' => 100,
            'medium' => 65,
            default => 30,
        };

        return $riskScore + (\count($flow['riskSignals']) * 8) + ('at_risk' === $flow['currentPhase']['status'] ? 12 : 0);
    }

    private function highestRiskLevel(string $left, string $right): string
    {
        $weights = ['low' => 1, 'medium' => 2, 'high' => 3];

        return ($weights[$right] ?? 1) > ($weights[$left] ?? 1) ? $right : $left;
    }

    /**
     * @param Onboardingplan[] $plans
     */
    private function buildPlanStatusChart(ChartBuilderInterface $chartBuilder, array $plans, OnboardingLanguageContext $onboardingLanguageContext, string $selectedLanguage): Chart
    {
        $counts = [
            Onboardingplan::STATUS_PENDING => 0,
            Onboardingplan::STATUS_IN_PROGRESS => 0,
            Onboardingplan::STATUS_COMPLETED => 0,
            Onboardingplan::STATUS_ON_HOLD => 0,
        ];

        foreach ($plans as $plan) {
            $status = $plan->getStatus();
            if (isset($counts[$status])) {
                ++$counts[$status];
            }
        }

        $translatedLabels = $onboardingLanguageContext->translateMap([
            'pending' => 'Pending',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'on_hold' => 'On Hold',
        ], $selectedLanguage);

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => [
                $translatedLabels['pending'],
                $translatedLabels['in_progress'],
                $translatedLabels['completed'],
                $translatedLabels['on_hold'],
            ],
            'datasets' => [[
                'label' => $onboardingLanguageContext->translateMap([
                    'dataset_label' => 'Plans by status',
                ], $selectedLanguage)['dataset_label'],
                'data' => [
                    $counts[Onboardingplan::STATUS_PENDING],
                    $counts[Onboardingplan::STATUS_IN_PROGRESS],
                    $counts[Onboardingplan::STATUS_COMPLETED],
                    $counts[Onboardingplan::STATUS_ON_HOLD],
                ],
                'backgroundColor' => [
                    'rgba(245, 158, 11, 0.88)',
                    'rgba(37, 99, 235, 0.88)',
                    'rgba(22, 163, 74, 0.88)',
                    'rgba(124, 58, 237, 0.88)',
                ],
                'borderColor' => [
                    'rgb(245, 158, 11)',
                    'rgb(37, 99, 235)',
                    'rgb(22, 163, 74)',
                    'rgb(124, 58, 237)',
                ],
                'borderWidth' => 2,
                'hoverOffset' => 10,
            ]],
        ]);
        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 18,
                    ],
                ],
            ],
            'cutout' => '62%',
        ]);

        return $chart;
    }

    /**
     * @param Onboardingplan[] $plans
     * @return array{
     *     title: string,
     *     text: string,
     *     priority_label: string,
     *     priority_reason: string,
     *     next_deadline: string,
     *     qr_readiness: string,
     *     candidate_coverage: string
     * }
     */
    private function buildPlanFocus(array $plans): array
    {
        if ([] === $plans) {
            return [
                'title' => 'No visible plans',
                'text' => 'Once plans are created, the focus panel will highlight what to review next.',
                'priority_label' => 'Nothing queued',
                'priority_reason' => 'There is no active onboarding workload in the current view.',
                'next_deadline' => 'No deadline',
                'qr_readiness' => '0 ready',
                'candidate_coverage' => '0 candidates',
            ];
        }

        $overduePlans = array_values(array_filter($plans, static fn (Onboardingplan $plan): bool => $plan->isOverdue()));
        $activePlans = array_values(array_filter($plans, static fn (Onboardingplan $plan): bool => Onboardingplan::STATUS_IN_PROGRESS === $plan->getStatus()));

        $priorityPlan = $this->pickPriorityPlan($plans);
        $nextDeadline = $this->findNextDeadlineLabel($plans);
        $qrReadyCount = count(array_filter($plans, static fn (Onboardingplan $plan): bool => null !== $plan->getQrToken() && '' !== trim((string) $plan->getQrToken())));

        $candidateIds = [];
        foreach ($plans as $plan) {
            if (null !== $plan->getUser()) {
                $candidateIds[(int) $plan->getUser()->getUserId()] = true;
            }
        }

        if ([] !== $overduePlans) {
            $title = 'Attention needed';
            $text = sprintf('%d plan%s currently need intervention before the flow stalls further.', count($overduePlans), 1 === count($overduePlans) ? '' : 's');
        } elseif ([] !== $activePlans) {
            $title = 'Flow is moving';
            $text = sprintf('%d plan%s are actively progressing right now.', count($activePlans), 1 === count($activePlans) ? '' : 's');
        } elseif (count($plans) === count(array_filter($plans, static fn (Onboardingplan $plan): bool => $plan->isCompleted()))) {
            $title = 'Everything is settled';
            $text = 'All visible plans are currently completed.';
        } else {
            $title = 'Ready for the next step';
            $text = 'The current plans are queued and waiting for the next onboarding actions.';
        }

        $priorityLabel = $priorityPlan ? sprintf(
            '%s %s',
            trim((string) $priorityPlan->getUser()?->getFirstName() . ' ' . (string) $priorityPlan->getUser()?->getLastName()),
            sprintf('(Plan #%d)', (int) $priorityPlan->getPlanId())
        ) : 'No priority plan';

        $priorityReason = $priorityPlan
            ? sprintf(
                'Currently %s%s.',
                strtolower(str_replace('_', ' ', (string) $priorityPlan->getStatus())),
                $priorityPlan->getDeadline() ? sprintf(' with deadline %s', $priorityPlan->getDeadline()->format('Y-m-d')) : ''
            )
            : 'No plan needs immediate review.';

        return [
            'title' => $title,
            'text' => $text,
            'priority_label' => $priorityLabel,
            'priority_reason' => $priorityReason,
            'next_deadline' => $nextDeadline,
            'qr_readiness' => sprintf('%d of %d ready', $qrReadyCount, count($plans)),
            'candidate_coverage' => sprintf('%d candidate%s', count($candidateIds), 1 === count($candidateIds) ? '' : 's'),
        ];
    }

    /**
     * @param Onboardingplan[] $plans
     */
    private function pickPriorityPlan(array $plans): ?Onboardingplan
    {
        usort($plans, static function (Onboardingplan $left, Onboardingplan $right): int {
            $score = static function (Onboardingplan $plan): int {
                return match ($plan->getStatus()) {
                    Onboardingplan::STATUS_ON_HOLD => 0,
                    Onboardingplan::STATUS_IN_PROGRESS => 1,
                    Onboardingplan::STATUS_PENDING => 2,
                    Onboardingplan::STATUS_COMPLETED => 3,
                    default => 4,
                };
            };

            $scoreCompare = $score($left) <=> $score($right);
            if (0 !== $scoreCompare) {
                return $scoreCompare;
            }

            $leftDeadline = $left->getDeadline()?->getTimestamp() ?? PHP_INT_MAX;
            $rightDeadline = $right->getDeadline()?->getTimestamp() ?? PHP_INT_MAX;

            if ($leftDeadline !== $rightDeadline) {
                return $leftDeadline <=> $rightDeadline;
            }

            return (int) $right->getPlanId() <=> (int) $left->getPlanId();
        });

        return $plans[0] ?? null;
    }

    /**
     * @param Onboardingplan[] $plans
     */
    private function findNextDeadlineLabel(array $plans): string
    {
        $timestamps = [];

        foreach ($plans as $plan) {
            if (null !== $plan->getDeadline() && !$plan->isCompleted()) {
                $timestamps[] = $plan->getDeadline()->getTimestamp();
            }
        }

        if ([] === $timestamps) {
            return 'No deadline';
        }

        sort($timestamps);

        return date('Y-m-d', $timestamps[0]);
    }

    /**
     * @param array<int, \App\Entity\Onboardingtask> $tasks
     * @return array{
     *     ui: array<string, string>,
     *     statuses: array<string, string>,
     *     tasks: array<int, array<string, string>>
     * }
     */
    private function buildQrTranslation(Onboardingplan $plan, array $tasks, LibreTranslateService $libreTranslateService, string $selectedLanguage): array
    {
        $uiTexts = [
            'mobile_view' => 'Hirely Mobile View',
            'plan_title' => sprintf('Onboarding Plan #%d', (int) $plan->getPlanId()),
            'assigned_to' => 'Assigned to',
            'status' => 'Status',
            'deadline' => 'Deadline',
            'tasks' => 'Tasks',
            'no_deadline' => 'No deadline',
            'attachment' => 'Attachment',
            'no_attachment' => 'No attachment',
            'open_file' => 'Open file',
            'no_description' => 'No description added for this task yet.',
            'no_tasks_yet' => 'No tasks yet',
            'no_tasks_title' => 'This onboarding plan has no tasks yet.',
            'no_tasks_text' => 'When tasks are added, they will appear here with any linked attachments.',
        ];

        $statusTexts = [
            Onboardingplan::STATUS_PENDING => 'Pending',
            Onboardingplan::STATUS_IN_PROGRESS => 'In Progress',
            Onboardingplan::STATUS_COMPLETED => 'Completed',
            Onboardingplan::STATUS_ON_HOLD => 'On Hold',
            \App\Entity\Onboardingtask::STATUS_NOT_STARTED => 'Not Started',
            \App\Entity\Onboardingtask::STATUS_IN_PROGRESS => 'In Progress',
            \App\Entity\Onboardingtask::STATUS_COMPLETED => 'Completed',
            \App\Entity\Onboardingtask::STATUS_BLOCKED => 'Blocked',
            \App\Entity\Onboardingtask::STATUS_ON_HOLD => 'On Hold',
        ];

        $taskTexts = [];
        foreach ($tasks as $task) {
            $taskTexts['task_title_' . $task->getTaskId()] = $task->getTitle() ?: 'Untitled task';
            $taskTexts['task_description_' . $task->getTaskId()] = $task->getDescription() ?: $uiTexts['no_description'];
            $taskTexts['task_attachment_' . $task->getTaskId()] = $task->hasAttachment() ? $task->getAttachmentLabel() : $uiTexts['no_attachment'];
        }

        $translatedUi = $libreTranslateService->translateMap($uiTexts, $selectedLanguage);
        $translatedStatuses = $libreTranslateService->translateMap($statusTexts, $selectedLanguage);
        $translatedTasks = $libreTranslateService->translateMap($taskTexts, $selectedLanguage);

        $tasksById = [];
        foreach ($tasks as $task) {
            $taskId = (int) $task->getTaskId();
            $tasksById[$taskId] = [
                'title' => $translatedTasks['task_title_' . $taskId] ?? ($task->getTitle() ?: 'Untitled task'),
                'description' => $translatedTasks['task_description_' . $taskId] ?? ($task->getDescription() ?: $uiTexts['no_description']),
                'attachment' => $task->hasAttachment()
                    ? $task->getAttachmentLabel()
                    : ($translatedTasks['task_attachment_' . $taskId] ?? $uiTexts['no_attachment']),
            ];
        }

        return [
            'ui' => $translatedUi,
            'statuses' => $translatedStatuses,
            'tasks' => $tasksById,
        ];
    }
}
