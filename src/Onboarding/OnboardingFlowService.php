<?php

namespace App\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;

final class OnboardingFlowService
{
    public const PHASE_PRE_ARRIVAL = 'pre_arrival';
    public const PHASE_FIRST_DAY = 'first_day';
    public const PHASE_FIRST_WEEK = 'first_week';
    public const PHASE_COMPLETION = 'completion';

    private const PHASE_ORDER = [
        self::PHASE_PRE_ARRIVAL,
        self::PHASE_FIRST_DAY,
        self::PHASE_FIRST_WEEK,
        self::PHASE_COMPLETION,
    ];

    /**
     * @param Onboardingtask[] $tasks
     * @return array{
     *     planId: int|null,
     *     progressPercent: int,
     *     readinessScore: int,
     *     riskLevel: string,
     *     currentPhase: array{code: string, phaseLabel: string, status: string},
     *     timeline: array<int, array{
     *         code: string,
     *         phaseLabel: string,
     *         status: string,
     *         taskCount: int,
     *         completedCount: int,
     *         progressPercent: int,
     *         primaryTaskId: int|null,
     *         primaryTaskTitle: string|null
     *     }>,
     *     nextActions: array<int, array{
     *         taskId: int|null,
     *         title: string,
     *         detail: string,
     *         priority: string,
     *         actionType: string
     *     }>,
     *     riskSignals: array<int, array{
     *         code: string,
     *         label: string,
     *         severity: string,
     *         count: int
     *     }>,
     *     smartSummary: string
     * }
     */
    public function buildPlanFlow(Onboardingplan $plan, array $tasks): array
    {
        $today = new \DateTimeImmutable('today');
        $phaseBuckets = [];

        foreach (self::PHASE_ORDER as $phaseCode) {
            $phaseBuckets[$phaseCode] = [
                'tasks' => [],
                'completed' => 0,
                'status' => 'upcoming',
            ];
        }

        $completedCount = 0;
        $blockedCount = 0;
        $onHoldCount = 0;
        $inProgressCount = 0;
        $notStartedCount = 0;
        $overdueCount = 0;
        $missingAttachmentCount = 0;

        foreach ($tasks as $task) {
            $phaseCode = $this->resolvePhaseCode($plan, $task, $today);
            $phaseBuckets[$phaseCode]['tasks'][] = $task;

            $status = (string) $task->getStatus();
            if (Onboardingtask::STATUS_COMPLETED === $status) {
                ++$completedCount;
                ++$phaseBuckets[$phaseCode]['completed'];
                if (!$task->hasAttachment()) {
                    ++$missingAttachmentCount;
                }
            } elseif (Onboardingtask::STATUS_BLOCKED === $status) {
                ++$blockedCount;
            } elseif (Onboardingtask::STATUS_ON_HOLD === $status) {
                ++$onHoldCount;
            } elseif (Onboardingtask::STATUS_IN_PROGRESS === $status) {
                ++$inProgressCount;
            } else {
                ++$notStartedCount;
            }

            if ($this->isTaskOverdue($task, $today)) {
                ++$overdueCount;
            }
        }

        $totalTasks = \count($tasks);
        $progressPercent = $totalTasks > 0 ? (int) round(($completedCount / $totalTasks) * 100) : 0;

        $riskSignals = [];
        if ($blockedCount > 0) {
            $riskSignals[] = [
                'code' => 'blocked',
                'label' => sprintf('%d blocked task%s', $blockedCount, 1 === $blockedCount ? '' : 's'),
                'severity' => 'high',
                'count' => $blockedCount,
            ];
        }

        if ($overdueCount > 0) {
            $riskSignals[] = [
                'code' => 'overdue',
                'label' => sprintf('%d overdue task%s', $overdueCount, 1 === $overdueCount ? '' : 's'),
                'severity' => 'high',
                'count' => $overdueCount,
            ];
        }

        if ($onHoldCount > 0) {
            $riskSignals[] = [
                'code' => 'on_hold',
                'label' => sprintf('%d task%s on hold', $onHoldCount, 1 === $onHoldCount ? '' : 's'),
                'severity' => 'medium',
                'count' => $onHoldCount,
            ];
        }

        if ($missingAttachmentCount > 0) {
            $riskSignals[] = [
                'code' => 'missing_proof',
                'label' => sprintf('%d completed task%s missing proof', $missingAttachmentCount, 1 === $missingAttachmentCount ? '' : 's'),
                'severity' => 'medium',
                'count' => $missingAttachmentCount,
            ];
        }

        $riskLevel = $blockedCount > 0 || $overdueCount > 0
            ? 'high'
            : (($onHoldCount > 0 || $missingAttachmentCount > 0) ? 'medium' : 'low');

        $readinessScore = $this->buildReadinessScore(
            $progressPercent,
            $blockedCount,
            $overdueCount,
            $onHoldCount,
            $missingAttachmentCount,
            $inProgressCount
        );

        $timeline = [];
        foreach (self::PHASE_ORDER as $phaseCode) {
            /** @var Onboardingtask[] $phaseTasks */
            $phaseTasks = $phaseBuckets[$phaseCode]['tasks'];
            $phaseStatus = $this->derivePhaseStatus($phaseTasks, $today);
            $phaseBuckets[$phaseCode]['status'] = $phaseStatus;
            $primaryTask = $this->pickPrimaryTask($phaseTasks, $today);
            $phaseTaskCount = \count($phaseTasks);
            $phaseCompletedCount = $phaseBuckets[$phaseCode]['completed'];

            $timeline[] = [
                'code' => $phaseCode,
                'phaseLabel' => $this->phaseLabel($phaseCode),
                'status' => $phaseStatus,
                'taskCount' => $phaseTaskCount,
                'completedCount' => $phaseCompletedCount,
                'progressPercent' => $phaseTaskCount > 0 ? (int) round(($phaseCompletedCount / $phaseTaskCount) * 100) : 0,
                'primaryTaskId' => $primaryTask?->getTaskId(),
                'primaryTaskTitle' => $primaryTask?->getTitle(),
            ];
        }

        $currentPhaseCode = $this->resolveCurrentPhaseCode($phaseBuckets, $riskLevel);
        $nextActions = $this->buildNextActions($tasks, $today);

        return [
            'planId' => $plan->getPlanId(),
            'progressPercent' => $progressPercent,
            'readinessScore' => $readinessScore,
            'riskLevel' => $riskLevel,
            'currentPhase' => [
                'code' => $currentPhaseCode,
                'phaseLabel' => $this->phaseLabel($currentPhaseCode),
                'status' => $phaseBuckets[$currentPhaseCode]['status'],
            ],
            'timeline' => $timeline,
            'nextActions' => $nextActions,
            'riskSignals' => $riskSignals,
            'smartSummary' => $this->buildSmartSummary(
                $progressPercent,
                $riskLevel,
                $currentPhaseCode,
                $blockedCount,
                $overdueCount,
                $missingAttachmentCount,
                $nextActions
            ),
        ];
    }

    private function resolvePhaseCode(Onboardingplan $plan, Onboardingtask $task, \DateTimeImmutable $today): string
    {
        if (Onboardingtask::STATUS_COMPLETED === $task->getStatus()) {
            return self::PHASE_COMPLETION;
        }

        if ($this->isTaskOverdue($task, $today) || \in_array($task->getStatus(), [Onboardingtask::STATUS_BLOCKED, Onboardingtask::STATUS_ON_HOLD], true)) {
            return self::PHASE_FIRST_DAY;
        }

        $taskDeadline = $task->getDeadline();
        $planDeadline = $plan->getDeadline();

        if ($taskDeadline instanceof \DateTimeInterface && $planDeadline instanceof \DateTimeInterface) {
            $taskDate = \DateTimeImmutable::createFromInterface($taskDeadline)->setTime(0, 0);
            $anchor = \DateTimeImmutable::createFromInterface($planDeadline)->setTime(0, 0);

            if ($taskDate <= $anchor->modify('-1 day')) {
                return self::PHASE_PRE_ARRIVAL;
            }

            if ($taskDate <= $anchor->modify('+1 day')) {
                return self::PHASE_FIRST_DAY;
            }

            if ($taskDate <= $anchor->modify('+7 days')) {
                return self::PHASE_FIRST_WEEK;
            }

            return self::PHASE_COMPLETION;
        }

        return match ($task->getStatus()) {
            Onboardingtask::STATUS_IN_PROGRESS => self::PHASE_FIRST_WEEK,
            default => self::PHASE_PRE_ARRIVAL,
        };
    }

    /**
     * @param Onboardingtask[] $tasks
     */
    private function derivePhaseStatus(array $tasks, \DateTimeImmutable $today): string
    {
        if ([] === $tasks) {
            return 'upcoming';
        }

        $total = \count($tasks);
        $completed = 0;
        $hasBlocked = false;
        $hasActive = false;
        $hasOverdue = false;

        foreach ($tasks as $task) {
            $status = (string) $task->getStatus();
            if (Onboardingtask::STATUS_COMPLETED === $status) {
                ++$completed;
                continue;
            }

            if (\in_array($status, [Onboardingtask::STATUS_BLOCKED, Onboardingtask::STATUS_ON_HOLD], true)) {
                $hasBlocked = true;
            }

            if (\in_array($status, [Onboardingtask::STATUS_IN_PROGRESS, Onboardingtask::STATUS_BLOCKED, Onboardingtask::STATUS_ON_HOLD], true)) {
                $hasActive = true;
            }

            if ($this->isTaskOverdue($task, $today)) {
                $hasOverdue = true;
            }
        }

        if ($completed === $total) {
            return 'completed';
        }

        if ($hasBlocked || $hasOverdue) {
            return 'at_risk';
        }

        if ($hasActive || $completed > 0) {
            return 'active';
        }

        return 'upcoming';
    }

    private function resolveCurrentPhaseCode(array $phaseBuckets, string $riskLevel): string
    {
        if ('high' === $riskLevel) {
            foreach ([self::PHASE_FIRST_DAY, self::PHASE_FIRST_WEEK, self::PHASE_PRE_ARRIVAL] as $phaseCode) {
                if ('at_risk' === $phaseBuckets[$phaseCode]['status']) {
                    return $phaseCode;
                }
            }
        }

        foreach (self::PHASE_ORDER as $phaseCode) {
            if (\in_array($phaseBuckets[$phaseCode]['status'], ['active', 'at_risk'], true)) {
                return $phaseCode;
            }
        }

        foreach (self::PHASE_ORDER as $phaseCode) {
            if ([] !== $phaseBuckets[$phaseCode]['tasks']) {
                return $phaseCode;
            }
        }

        return self::PHASE_PRE_ARRIVAL;
    }

    /**
     * @param Onboardingtask[] $tasks
     * @return array<int, array{taskId: int|null, title: string, detail: string, priority: string, actionType: string}>
     */
    private function buildNextActions(array $tasks, \DateTimeImmutable $today): array
    {
        usort($tasks, function (Onboardingtask $left, Onboardingtask $right) use ($today): int {
            return $this->taskPriorityScore($right, $today) <=> $this->taskPriorityScore($left, $today);
        });

        $actions = [];
        foreach (\array_slice($tasks, 0, 3) as $task) {
            $actions[] = [
                'taskId' => $task->getTaskId(),
                'title' => $task->getTitle() ?: 'Untitled task',
                'detail' => $this->buildActionDetail($task, $today),
                'priority' => $this->priorityFromTask($task, $today),
                'actionType' => \in_array($task->getStatus(), [Onboardingtask::STATUS_BLOCKED, Onboardingtask::STATUS_ON_HOLD], true)
                    ? TaskRecommendation::ACTION_OPEN_UPDATE
                    : TaskRecommendation::ACTION_SELECT_TASK,
            ];
        }

        if ([] === $actions) {
            $actions[] = [
                'taskId' => null,
                'title' => 'No active actions',
                'detail' => 'This plan has no immediate workflow action right now.',
                'priority' => 'low',
                'actionType' => TaskRecommendation::ACTION_REFRESH,
            ];
        }

        return $actions;
    }

    private function buildReadinessScore(
        int $progressPercent,
        int $blockedCount,
        int $overdueCount,
        int $onHoldCount,
        int $missingAttachmentCount,
        int $inProgressCount,
    ): int {
        $score = 24 + (int) round($progressPercent * 0.62);
        $score += min(8, $inProgressCount * 3);
        $score -= ($blockedCount * 16);
        $score -= ($overdueCount * 12);
        $score -= ($onHoldCount * 8);
        $score -= ($missingAttachmentCount * 6);

        return max(10, min(98, $score));
    }

    /**
     * @param Onboardingtask[] $tasks
     */
    private function pickPrimaryTask(array $tasks, \DateTimeImmutable $today): ?Onboardingtask
    {
        if ([] === $tasks) {
            return null;
        }

        usort($tasks, fn (Onboardingtask $left, Onboardingtask $right): int => $this->taskPriorityScore($right, $today) <=> $this->taskPriorityScore($left, $today));

        return $tasks[0] ?? null;
    }

    private function taskPriorityScore(Onboardingtask $task, \DateTimeImmutable $today): int
    {
        $score = match ($task->getStatus()) {
            Onboardingtask::STATUS_BLOCKED => 100,
            Onboardingtask::STATUS_ON_HOLD => 88,
            Onboardingtask::STATUS_IN_PROGRESS => 74,
            Onboardingtask::STATUS_NOT_STARTED => 54,
            Onboardingtask::STATUS_COMPLETED => $task->hasAttachment() ? 10 : 60,
            default => 20,
        };

        if ($this->isTaskOverdue($task, $today)) {
            $score += 22;
        } elseif ($task->getDeadline() instanceof \DateTimeInterface) {
            $daysUntilDeadline = (int) $today->diff(\DateTimeImmutable::createFromInterface($task->getDeadline())->setTime(0, 0))->format('%r%a');
            if ($daysUntilDeadline <= 1) {
                $score += 12;
            } elseif ($daysUntilDeadline <= 3) {
                $score += 8;
            }
        }

        return $score;
    }

    private function priorityFromTask(Onboardingtask $task, \DateTimeImmutable $today): string
    {
        if (\in_array($task->getStatus(), [Onboardingtask::STATUS_BLOCKED, Onboardingtask::STATUS_ON_HOLD], true) || $this->isTaskOverdue($task, $today)) {
            return 'high';
        }

        if (\in_array($task->getStatus(), [Onboardingtask::STATUS_IN_PROGRESS, Onboardingtask::STATUS_COMPLETED], true) && !$task->hasAttachment()) {
            return 'medium';
        }

        return Onboardingtask::STATUS_NOT_STARTED === $task->getStatus() ? 'medium' : 'low';
    }

    private function buildActionDetail(Onboardingtask $task, \DateTimeImmutable $today): string
    {
        $status = (string) $task->getStatus();

        if (Onboardingtask::STATUS_BLOCKED === $status) {
            return 'This task is blocked and is slowing the onboarding flow.';
        }

        if (Onboardingtask::STATUS_ON_HOLD === $status) {
            return 'This task is on hold and needs follow-up before the flow can progress.';
        }

        if (Onboardingtask::STATUS_COMPLETED === $status && !$task->hasAttachment()) {
            return 'The task is marked completed but still needs supporting proof.';
        }

        if ($this->isTaskOverdue($task, $today)) {
            return 'This task is overdue and should be resolved immediately.';
        }

        if (Onboardingtask::STATUS_IN_PROGRESS === $status) {
            return 'This is the strongest active task to push forward next.';
        }

        return 'This is the next clean task to start in the flow.';
    }

    private function buildSmartSummary(
        int $progressPercent,
        string $riskLevel,
        string $currentPhaseCode,
        int $blockedCount,
        int $overdueCount,
        int $missingAttachmentCount,
        array $nextActions,
    ): string {
        $phaseLabel = $this->phaseLabel($currentPhaseCode);
        $topAction = $nextActions[0]['title'] ?? 'the next task';

        if ('high' === $riskLevel) {
            if ($blockedCount > 0) {
                return sprintf('The flow is in %s and needs intervention because blocked work is holding back progress. Start with %s.', strtolower($phaseLabel), $topAction);
            }

            return sprintf('The flow is in %s and at risk because deadlines are slipping. %s should be handled first.', strtolower($phaseLabel), $topAction);
        }

        if ('medium' === $riskLevel && $missingAttachmentCount > 0) {
            return sprintf('The flow is moving through %s with %d%% progress, but completed work still needs proof attachments.', strtolower($phaseLabel), $progressPercent);
        }

        if ($progressPercent >= 100) {
            return 'The flow is complete and ready for final review.';
        }

        return sprintf('The flow is progressing through %s with %d%% completion. The next best move is %s.', strtolower($phaseLabel), $progressPercent, $topAction);
    }

    private function isTaskOverdue(Onboardingtask $task, \DateTimeImmutable $today): bool
    {
        if (Onboardingtask::STATUS_COMPLETED === $task->getStatus() || !$task->getDeadline() instanceof \DateTimeInterface) {
            return false;
        }

        $deadline = \DateTimeImmutable::createFromInterface($task->getDeadline())->setTime(0, 0);

        return $deadline < $today;
    }

    private function phaseLabel(string $phaseCode): string
    {
        return match ($phaseCode) {
            self::PHASE_PRE_ARRIVAL => 'Pre-arrival',
            self::PHASE_FIRST_DAY => 'First day',
            self::PHASE_FIRST_WEEK => 'First week',
            self::PHASE_COMPLETION => 'Completion',
            default => 'Flow',
        };
    }
}
