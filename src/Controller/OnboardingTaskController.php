<?php

namespace App\Controller;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Form\OnboardingTaskType;
use App\Onboarding\AttachmentUploadConfiguration;
use App\Onboarding\LibreTranslateService;
use App\Onboarding\LocalTaskAttachmentStorage;
use App\Onboarding\OnboardingLanguageContext;
use App\Onboarding\OnboardingPlanStatusManager;
use App\Onboarding\TaskDecisionGuideService;
use App\Onboarding\TaskRecommendation;
use App\Onboarding\ViewerContext;
use App\Repository\OnboardingtaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class OnboardingTaskController extends AbstractController
{
    #[Route('/attachments/upload', name: 'app_task_attachment_upload', methods: ['POST'])]
    public function uploadAttachment(Request $request, ViewerContext $viewerContext, AttachmentUploadConfiguration $attachmentUploadConfiguration, LocalTaskAttachmentStorage $localTaskAttachmentStorage): JsonResponse
    {
        if (!$viewerContext->getCurrentUser()) {
            return $this->json(['error' => ['message' => 'No active user found for this session.']], Response::HTTP_FORBIDDEN);
        }

        $uploadedFile = $request->files->get('file');
        if (!$uploadedFile) {
            return $this->json(['error' => ['message' => 'Choose a file before uploading.']], Response::HTTP_BAD_REQUEST);
        }

        if ($attachmentUploadConfiguration->isEnabled()) {
            return $this->json([
                'error' => ['message' => 'Direct browser upload should use the configured external provider.'],
            ], Response::HTTP_BAD_REQUEST);
        }

        $storedFile = $localTaskAttachmentStorage->store($uploadedFile);
        $publicUrl = rtrim($request->getSchemeAndHttpHost(), '/') . $storedFile['public_path'];

        return $this->json([
            'secure_url' => $publicUrl,
            'public_id' => 'local/' . $storedFile['stored_name'],
            'original_filename' => pathinfo($storedFile['original_name'], \PATHINFO_FILENAME),
            'format' => pathinfo($storedFile['stored_name'], \PATHINFO_EXTENSION),
            'resource_type' => 'raw',
            'content_type' => $storedFile['content_type'],
        ]);
    }

    #[Route('/admin/plans/{id}/tasks', name: 'app_admin_plan_tasks')]
    #[Route('/workspace/plans/{id}/tasks', name: 'app_workspace_plan_tasks')]
    public function index(Request $request, Onboardingplan $plan, OnboardingtaskRepository $taskRepository, ViewerContext $viewerContext, TaskDecisionGuideService $taskDecisionGuideService, ChartBuilderInterface $chartBuilder, LibreTranslateService $libreTranslateService, OnboardingLanguageContext $onboardingLanguageContext): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canViewPlan($plan)) {
            $this->addFlash('error', 'You are not allowed to view tasks for this onboarding plan.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        $searchTerm = trim((string) $request->query->get('q', ''));
        $caseSensitive = $request->query->getBoolean('case_sensitive');
        $filters = [
            'status' => trim((string) $request->query->get('status', '')),
            'sort' => trim((string) $request->query->get('sort', 'newest')),
            'attachment_only' => $request->query->getBoolean('attachment_only'),
        ];
        $selectedLanguage = $onboardingLanguageContext->resolveFromRequest($request);
        $tasks = $taskRepository->findByPlan($plan, $searchTerm, $caseSensitive, $filters);
        $taskMetrics = $this->buildTaskMetrics($tasks);
        $taskRecommendations = \array_slice($taskDecisionGuideService->buildRecommendations($viewerContext->getRoleId() ?? ViewerContext::ROLE_CANDIDATE, $tasks), 0, 5);
        $translatedTaskUi = $this->buildTranslatedTaskUi(
            $tasks,
            $taskRecommendations,
            $libreTranslateService,
            $selectedLanguage,
            $viewerContext->isCandidate() ? 'My Plan Tasks' : 'Plan Tasks',
        );
        $viewData = [
            'plan' => $plan,
            'tasks' => $tasks,
            'search_term' => $searchTerm,
            'case_sensitive' => $caseSensitive,
            'selected_status' => $filters['status'],
            'selected_sort' => $filters['sort'],
            'attachment_only' => $filters['attachment_only'],
            'selected_language' => $selectedLanguage,
            'translation_languages' => $libreTranslateService->getLanguageChoices(),
            'translation_enabled' => $libreTranslateService->isEnabled(),
            'task_translation' => $translatedTaskUi,
            'task_metrics' => $taskMetrics,
            'task_status_chart' => $this->buildTaskStatusChart($chartBuilder, $taskMetrics, $onboardingLanguageContext, $selectedLanguage),
            'task_progress_chart' => $this->buildTaskProgressChart($chartBuilder, $taskMetrics, $onboardingLanguageContext, $selectedLanguage),
            'task_recommendations' => $taskRecommendations,
            'task_guide_overview' => $this->buildTaskGuideOverview($tasks, $taskMetrics, $taskRecommendations),
            'task_status_choices' => Onboardingtask::getStatusChoices(),
        ];

        if ($request->isXmlHttpRequest()) {
            return $this->render('admin/tasks/_results.html.twig', $viewData);
        }

        return $this->render('admin/tasks/index.html.twig', $viewData);
    }

    #[Route('/admin/plans/{id}/tasks/new', name: 'app_admin_plan_tasks_new')]
    #[Route('/workspace/plans/{id}/tasks/new', name: 'app_workspace_plan_tasks_new')]
    public function new(Onboardingplan $plan, Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext, AttachmentUploadConfiguration $attachmentUploadConfiguration, OnboardingPlanStatusManager $onboardingPlanStatusManager): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        if (!$viewerContext->canCreateTasks() || !$viewerContext->canViewPlan($plan)) {
            $this->addFlash('error', 'Only admin and recruiter users can create onboarding tasks.');

            return $this->redirectToRoute($this->planTasksRoute($request), [
                'id' => $plan->getPlanId(),
            ]);
        }

        $task = new Onboardingtask();
        $plan->addOnboardingtask($task);
        $task->setStatus(Onboardingtask::STATUS_NOT_STARTED);

        $form = $this->createForm(OnboardingTaskType::class, $task, [
            'editor_mode' => 'full',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$task->getStatus()) {
                $task->setStatus(Onboardingtask::STATUS_NOT_STARTED);
            }
            if (!$task->getFilePath()) {
                $task->clearAttachment();
            }

            $onboardingPlanStatusManager->syncPlanStatus($plan);
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute($this->planTasksRoute($request), [
                'id' => $plan->getPlanId(),
            ]);
        }

        return $this->render('admin/tasks/form.html.twig', [
            'form' => $form->createView(),
            'plan' => $plan,
            'page_title' => 'Add Task',
            'limited_editor' => false,
            'task' => $task,
            'attachment_upload' => $this->buildAttachmentUploadViewData($attachmentUploadConfiguration),
        ]);
    }

    #[Route('/admin/tasks/{id}/edit', name: 'app_admin_plan_tasks_edit')]
    #[Route('/workspace/tasks/{id}/edit', name: 'app_workspace_plan_tasks_edit')]
    public function edit(Onboardingtask $task, Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext, AttachmentUploadConfiguration $attachmentUploadConfiguration, OnboardingPlanStatusManager $onboardingPlanStatusManager): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        $plan = $task->getPlan();

        if (!$viewerContext->canEditTask($task)) {
            $this->addFlash('error', 'You are not allowed to update this onboarding task.');

            return $this->redirectToRoute($this->plansRoute($request));
        }

        if (!$task->getStatus()) {
            $task->setStatus(Onboardingtask::STATUS_NOT_STARTED);
        }

        $limitedEditor = !$viewerContext->canFullyEditTask($task);
        $form = $this->createForm(OnboardingTaskType::class, $task, [
            'editor_mode' => $limitedEditor ? 'candidate' : 'full',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$task->getStatus()) {
                $task->setStatus(Onboardingtask::STATUS_NOT_STARTED);
            }
            if (!$task->getFilePath()) {
                $task->clearAttachment();
            }

            $onboardingPlanStatusManager->syncPlanStatus($plan);
            $entityManager->flush();

            return $this->redirectToRoute($this->planTasksRoute($request), [
                'id' => $plan->getPlanId(),
            ]);
        }

        return $this->render('admin/tasks/form.html.twig', [
            'form' => $form->createView(),
            'plan' => $plan,
            'page_title' => $limitedEditor ? 'Update Task Progress' : 'Edit Task',
            'limited_editor' => $limitedEditor,
            'task' => $task,
            'attachment_upload' => $this->buildAttachmentUploadViewData($attachmentUploadConfiguration),
        ]);
    }

    #[Route('/admin/tasks/{id}/delete', name: 'app_admin_plan_tasks_delete', methods: ['POST'])]
    #[Route('/workspace/tasks/{id}/delete', name: 'app_workspace_plan_tasks_delete', methods: ['POST'])]
    public function delete(Onboardingtask $task, Request $request, EntityManagerInterface $entityManager, ViewerContext $viewerContext, OnboardingPlanStatusManager $onboardingPlanStatusManager): Response|RedirectResponse
    {
        if ($redirect = $this->redirectForArea($request, $viewerContext)) {
            return $redirect;
        }

        $plan = $task->getPlan();

        if (!$viewerContext->canDeleteTask($task)) {
            $this->addFlash('error', 'You are not allowed to delete this onboarding task.');

            return $this->redirectToRoute($this->planTasksRoute($request), [
                'id' => $plan->getPlanId(),
            ]);
        }

        if ($this->isCsrfTokenValid('delete_task_' . $task->getTaskId(), $request->request->get('_token'))) {
            $plan->removeOnboardingtask($task);
            $entityManager->remove($task);
            $onboardingPlanStatusManager->syncPlanStatus($plan);
            $entityManager->flush();
        }

        return $this->redirectToRoute($this->planTasksRoute($request), [
            'id' => $plan->getPlanId(),
        ]);
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

    private function planTasksRoute(Request $request): string
    {
        return $this->isAdminArea($request) ? 'app_admin_plan_tasks' : 'app_workspace_plan_tasks';
    }

    /**
     * @return array{enabled: bool, provider: string, cloud_name: string, unsigned_preset: string, upload_url: string}
     */
    private function buildAttachmentUploadViewData(AttachmentUploadConfiguration $attachmentUploadConfiguration): array
    {
        $provider = $attachmentUploadConfiguration->isEnabled() ? 'cloudinary' : 'local';

        return $attachmentUploadConfiguration->toViewData() + [
            'enabled' => true,
            'provider' => $provider,
            'upload_url' => $this->generateUrl('app_task_attachment_upload'),
        ];
    }

    /**
     * @param Onboardingtask[] $tasks
     * @param TaskRecommendation[] $taskRecommendations
     * @return array{
     *     ui: array<string, string>,
     *     statuses: array<string, string>,
     *     tasks: array<int, array<string, string>>,
     *     recommendations: array<int, array{message: string, reason: string, action_label: string}>
     * }
     */
    private function buildTranslatedTaskUi(array $tasks, array $taskRecommendations, LibreTranslateService $libreTranslateService, string $selectedLanguage, string $pageTitle): array
    {
        $uiTexts = [
            'page_title' => $pageTitle,
            'flow_title' => 'Task Flow',
            'flow_kicker' => 'Stay focused on progress, deadlines, attachment status, and the next useful action.',
            'find_task' => 'Find a task',
            'search_hint' => 'Use search and filters to narrow the task flow instantly.',
            'task_status_distribution' => 'Task Status Distribution',
            'task_status_distribution_text' => 'See how the current task flow is split across each status.',
            'completion_file_coverage' => 'Completion and File Coverage',
            'completion_file_coverage_text' => 'Track finished work and how much of the plan already has linked files.',
            'decision_guide' => 'Decision Guide',
            'decision_guide_text' => 'Live guidance based on task status mix, missing proof, urgency, and the next deadline.',
            'guide_health' => 'Guide Health',
            'current_focus' => 'Current focus',
            'guide_updates' => 'The guide updates from the visible task list and current filters.',
            'score' => 'score',
            'urgent_actions' => 'Urgent actions',
            'urgent_actions_note' => 'High-priority recommendations visible now',
            'due_soon' => 'Due soon',
            'due_soon_note' => 'Tasks due within the next 3 days',
            'missing_proof' => 'Missing proof',
            'missing_proof_note' => 'Completed tasks still missing evidence',
            'next_deadline' => 'Next deadline',
            'next_deadline_note' => 'Earliest active deadline in this plan',
            'attachment_ready' => 'Attachment ready',
            'no_attachment_yet' => 'No attachment linked yet',
            'untitled_task' => 'Untitled task',
            'deadline' => 'Deadline',
            'no_deadline' => 'No deadline',
            'attachment' => 'Attachment',
            'not_attached' => 'Not attached',
            'open_file' => 'Open file',
            'edit' => 'Edit',
            'update' => 'Update',
            'delete' => 'Delete',
            'no_matching_tasks' => 'No matching tasks',
            'no_tasks_found' => 'No tasks found for',
            'no_tasks_search_text' => 'Try another keyword or clear the search to bring back the full task list for this plan.',
            'no_tasks_yet' => 'No tasks yet',
            'first_task_ready' => 'This plan is ready for its first task',
            'first_task_text' => 'Add a task to define the next onboarding action for this plan.',
        ];

        $statusTexts = [
            Onboardingtask::STATUS_NOT_STARTED => 'Not Started',
            Onboardingtask::STATUS_IN_PROGRESS => 'In Progress',
            Onboardingtask::STATUS_COMPLETED => 'Completed',
            Onboardingtask::STATUS_BLOCKED => 'Blocked',
            Onboardingtask::STATUS_ON_HOLD => 'On Hold',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];

        $taskTexts = [];
        foreach ($tasks as $task) {
            $taskTexts['task_title_' . $task->getTaskId()] = $task->getTitle() ?: $uiTexts['untitled_task'];
            $taskTexts['task_description_' . $task->getTaskId()] = $task->getDescription() ?: '';
            $taskTexts['task_attachment_state_' . $task->getTaskId()] = $task->hasAttachment() ? $uiTexts['attachment_ready'] : $uiTexts['no_attachment_yet'];
            $taskTexts['task_attachment_value_' . $task->getTaskId()] = $task->hasAttachment() ? $task->getAttachmentLabel() : $uiTexts['not_attached'];
        }

        $recommendationTexts = [];
        foreach ($taskRecommendations as $index => $recommendation) {
            $recommendationTexts['recommendation_message_' . $index] = $recommendation->getMessage();
            $recommendationTexts['recommendation_reason_' . $index] = $recommendation->getReason();
            $recommendationTexts['recommendation_action_' . $index] = $recommendation->getActionLabel();
        }

        $translatedUi = $libreTranslateService->translateMap($uiTexts, $selectedLanguage);
        $translatedStatuses = $libreTranslateService->translateMap($statusTexts, $selectedLanguage);
        $translatedTasks = $libreTranslateService->translateMap($taskTexts, $selectedLanguage);
        $translatedRecommendations = $libreTranslateService->translateMap($recommendationTexts, $selectedLanguage);

        $tasksById = [];
        foreach ($tasks as $task) {
            $taskId = (int) $task->getTaskId();
            $tasksById[$taskId] = [
                'title' => $translatedTasks['task_title_' . $taskId] ?? ($task->getTitle() ?: $uiTexts['untitled_task']),
                'description' => $translatedTasks['task_description_' . $taskId] ?? (string) $task->getDescription(),
                'attachment_state' => $translatedTasks['task_attachment_state_' . $taskId] ?? ($task->hasAttachment() ? $uiTexts['attachment_ready'] : $uiTexts['no_attachment_yet']),
                'attachment_value' => $task->hasAttachment()
                    ? $task->getAttachmentLabel()
                    : ($translatedTasks['task_attachment_value_' . $taskId] ?? $uiTexts['not_attached']),
            ];
        }

        $recommendationsByIndex = [];
        foreach ($taskRecommendations as $index => $recommendation) {
            $recommendationsByIndex[$index] = [
                'message' => $translatedRecommendations['recommendation_message_' . $index] ?? $recommendation->getMessage(),
                'reason' => $translatedRecommendations['recommendation_reason_' . $index] ?? $recommendation->getReason(),
                'action_label' => $translatedRecommendations['recommendation_action_' . $index] ?? $recommendation->getActionLabel(),
            ];
        }

        return [
            'ui' => $translatedUi,
            'statuses' => $translatedStatuses,
            'tasks' => $tasksById,
            'recommendations' => $recommendationsByIndex,
        ];
    }

    /**
     * @param Onboardingtask[] $tasks
     * @return array{total: int, completed: int, in_progress: int, blocked: int, on_hold: int, not_started: int, attachments: int}
     */
    private function buildTaskMetrics(array $tasks): array
    {
        $completed = 0;
        $inProgress = 0;
        $blocked = 0;
        $onHold = 0;
        $notStarted = 0;
        $attachments = 0;

        foreach ($tasks as $task) {
            if (Onboardingtask::STATUS_COMPLETED === $task->getStatus()) {
                ++$completed;
            }

            if (Onboardingtask::STATUS_IN_PROGRESS === $task->getStatus()) {
                ++$inProgress;
            }

            if (Onboardingtask::STATUS_BLOCKED === $task->getStatus()) {
                ++$blocked;
            }

            if (Onboardingtask::STATUS_ON_HOLD === $task->getStatus()) {
                ++$onHold;
            }

            if (Onboardingtask::STATUS_NOT_STARTED === $task->getStatus()) {
                ++$notStarted;
            }

            if ($task->hasAttachment()) {
                ++$attachments;
            }
        }

        return [
            'total' => \count($tasks),
            'completed' => $completed,
            'in_progress' => $inProgress,
            'blocked' => $blocked,
            'on_hold' => $onHold,
            'not_started' => $notStarted,
            'attachments' => $attachments,
        ];
    }

    /**
     * @param Onboardingtask[] $tasks
     * @param array{total: int, completed: int, in_progress: int, blocked: int, on_hold: int, not_started: int, attachments: int} $taskMetrics
     * @param TaskRecommendation[] $taskRecommendations
     * @return array{health_score: int, health_label: string, focus_label: string, due_soon_count: int, missing_proof_count: int, urgent_actions: int, next_deadline_label: string}
     */
    private function buildTaskGuideOverview(array $tasks, array $taskMetrics, array $taskRecommendations): array
    {
        $dueSoonCount = 0;
        $missingProofCount = 0;
        $urgentActions = 0;
        $nextDeadline = null;
        $today = new \DateTimeImmutable('today');
        $soonLimit = $today->modify('+3 days');

        foreach ($tasks as $task) {
            $deadline = $task->getDeadline();
            if ($deadline && Onboardingtask::STATUS_COMPLETED !== $task->getStatus()) {
                $deadlineDate = \DateTimeImmutable::createFromInterface($deadline)->setTime(0, 0);
                if ($deadlineDate <= $soonLimit) {
                    ++$dueSoonCount;
                }

                if (null === $nextDeadline || $deadlineDate < $nextDeadline) {
                    $nextDeadline = $deadlineDate;
                }
            }

            if (Onboardingtask::STATUS_COMPLETED === $task->getStatus() && !$task->hasAttachment()) {
                ++$missingProofCount;
            }
        }

        foreach ($taskRecommendations as $recommendation) {
            if (TaskRecommendation::PRIORITY_HIGH === $recommendation->getPriority()) {
                ++$urgentActions;
            }
        }

        $healthScore = 100;
        $healthScore -= ($taskMetrics['blocked'] * 18);
        $healthScore -= ($taskMetrics['on_hold'] * 10);
        $healthScore -= ($taskMetrics['not_started'] * 6);
        $healthScore -= ($missingProofCount * 7);
        $healthScore += ($taskMetrics['completed'] * 4);
        $healthScore += ($taskMetrics['attachments'] * 2);
        $healthScore = max(18, min(96, $healthScore));

        if ($healthScore >= 80) {
            $healthLabel = 'Healthy flow';
        } elseif ($healthScore >= 60) {
            $healthLabel = 'Watch closely';
        } else {
            $healthLabel = 'Needs attention';
        }

        if ($taskMetrics['blocked'] > 0) {
            $focusLabel = 'Resolve blockers';
        } elseif ($missingProofCount > 0) {
            $focusLabel = 'Collect missing proof';
        } elseif ($taskMetrics['in_progress'] > 0) {
            $focusLabel = 'Push active work forward';
        } elseif ($taskMetrics['not_started'] > 0) {
            $focusLabel = 'Kick off pending work';
        } else {
            $focusLabel = 'Maintain momentum';
        }

        $nextDeadlineLabel = $nextDeadline ? $nextDeadline->format('Y-m-d') : 'No active deadline';

        return [
            'health_score' => $healthScore,
            'health_label' => $healthLabel,
            'focus_label' => $focusLabel,
            'due_soon_count' => $dueSoonCount,
            'missing_proof_count' => $missingProofCount,
            'urgent_actions' => $urgentActions,
            'next_deadline_label' => $nextDeadlineLabel,
        ];
    }

    /**
     * @param array{total: int, completed: int, in_progress: int, blocked: int, on_hold: int, not_started: int, attachments: int} $taskMetrics
     */
    private function buildTaskStatusChart(ChartBuilderInterface $chartBuilder, array $taskMetrics, OnboardingLanguageContext $onboardingLanguageContext, string $selectedLanguage): Chart
    {
        $translatedLabels = $onboardingLanguageContext->translateMap([
            'not_started' => 'Not Started',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'blocked' => 'Blocked',
            'on_hold' => 'On Hold',
            'dataset_label' => 'Tasks by status',
        ], $selectedLanguage);

        $chart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => [
                $translatedLabels['not_started'],
                $translatedLabels['in_progress'],
                $translatedLabels['completed'],
                $translatedLabels['blocked'],
                $translatedLabels['on_hold'],
            ],
            'datasets' => [[
                'label' => $translatedLabels['dataset_label'],
                'data' => [
                    $taskMetrics['not_started'],
                    $taskMetrics['in_progress'],
                    $taskMetrics['completed'],
                    $taskMetrics['blocked'],
                    $taskMetrics['on_hold'],
                ],
                'backgroundColor' => [
                    'rgba(100, 116, 139, 0.88)',
                    'rgba(37, 99, 235, 0.88)',
                    'rgba(22, 163, 74, 0.88)',
                    'rgba(220, 38, 38, 0.88)',
                    'rgba(124, 58, 237, 0.88)',
                ],
                'borderColor' => [
                    'rgb(100, 116, 139)',
                    'rgb(37, 99, 235)',
                    'rgb(22, 163, 74)',
                    'rgb(220, 38, 38)',
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
     * @param array{total: int, completed: int, in_progress: int, blocked: int, on_hold: int, not_started: int, attachments: int} $taskMetrics
     */
    private function buildTaskProgressChart(ChartBuilderInterface $chartBuilder, array $taskMetrics, OnboardingLanguageContext $onboardingLanguageContext, string $selectedLanguage): Chart
    {
        $remainingTasks = max(0, $taskMetrics['total'] - $taskMetrics['completed']);
        $translatedLabels = $onboardingLanguageContext->translateMap([
            'completion' => 'Completion',
            'files' => 'Files',
            'completed' => 'Completed',
            'remaining' => 'Remaining / Missing',
        ], $selectedLanguage);

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => [$translatedLabels['completion'], $translatedLabels['files']],
            'datasets' => [
                [
                    'label' => $translatedLabels['completed'],
                    'data' => [$taskMetrics['completed'], $taskMetrics['attachments']],
                    'backgroundColor' => [
                        'rgba(22, 163, 74, 0.82)',
                        'rgba(255, 92, 40, 0.82)',
                    ],
                    'borderRadius' => 12,
                    'borderSkipped' => false,
                ],
                [
                    'label' => $translatedLabels['remaining'],
                    'data' => [$remainingTasks, max(0, $taskMetrics['total'] - $taskMetrics['attachments'])],
                    'backgroundColor' => [
                        'rgba(35, 30, 83, 0.14)',
                        'rgba(35, 30, 83, 0.14)',
                    ],
                    'borderRadius' => 12,
                    'borderSkipped' => false,
                ],
            ],
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
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ]);

        return $chart;
    }
}
