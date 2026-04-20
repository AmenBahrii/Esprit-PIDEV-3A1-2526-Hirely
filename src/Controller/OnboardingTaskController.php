<?php

namespace App\Controller;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Form\OnboardingTaskType;
use App\Onboarding\AttachmentUploadConfiguration;
use App\Onboarding\LibreTranslateService;
use App\Onboarding\LocalTaskAttachmentStorage;
use App\Onboarding\OnboardingLanguageContext;
use App\Onboarding\OnboardingFlowPresenter;
use App\Onboarding\OnboardingFlowService;
use App\Onboarding\OnboardingPlanStatusManager;
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
    public function index(Request $request, Onboardingplan $plan, OnboardingtaskRepository $taskRepository, ViewerContext $viewerContext, ChartBuilderInterface $chartBuilder, LibreTranslateService $libreTranslateService, OnboardingLanguageContext $onboardingLanguageContext, OnboardingFlowService $onboardingFlowService, OnboardingFlowPresenter $onboardingFlowPresenter): Response|RedirectResponse
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
        $translatedTaskUi = $this->buildTranslatedTaskUi(
            $tasks,
            $libreTranslateService,
            $selectedLanguage,
            $viewerContext->isCandidate() ? 'My Plan Tasks' : 'Plan Tasks',
        );
        $taskFlow = $onboardingFlowPresenter->translateFlow(
            $onboardingFlowService->buildPlanFlow($plan, $tasks),
            $selectedLanguage
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
            'task_flow' => $taskFlow,
            'task_metrics' => $taskMetrics,
            'task_status_chart' => $this->buildTaskStatusChart($chartBuilder, $taskMetrics, $onboardingLanguageContext, $selectedLanguage),
            'task_progress_chart' => $this->buildTaskProgressChart($chartBuilder, $taskMetrics, $onboardingLanguageContext, $selectedLanguage),
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
     * @return array{
     *     ui: array<string, string>,
     *     statuses: array<string, string>,
     *     tasks: array<int, array<string, string>>
     * }
     */
    private function buildTranslatedTaskUi(array $tasks, LibreTranslateService $libreTranslateService, string $selectedLanguage, string $pageTitle): array
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
            'flow_workspace' => 'Flow Workspace',
            'flow_workspace_text' => 'A live timeline of the onboarding flow with progress, risk, and the next best moves.',
            'readiness_score' => 'Readiness score',
            'progress' => 'Progress',
            'risk_level' => 'Risk level',
            'current_phase' => 'Current phase',
            'next_actions' => 'Next actions',
            'risk_signals' => 'Risk signals',
            'timeline' => 'Timeline',
            'tasks_in_phase' => 'tasks in phase',
            'primary_task' => 'Primary task',
            'priority_action' => 'Priority action',
            'signal_count' => 'Signal count',
            'phase_status' => 'Phase status',
            'phase_load' => 'Phase load',
            'all_clear' => 'All clear',
            'no_risk_signals' => 'No active risk signals.',
            'action_open_task' => 'Open task',
            'action_update' => 'Review task',
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

        $translatedUi = $libreTranslateService->translateMap($uiTexts, $selectedLanguage);
        $translatedStatuses = $libreTranslateService->translateMap($statusTexts, $selectedLanguage);
        $translatedTasks = $libreTranslateService->translateMap($taskTexts, $selectedLanguage);

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

        return [
            'ui' => $translatedUi,
            'statuses' => $translatedStatuses,
            'tasks' => $tasksById,
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
