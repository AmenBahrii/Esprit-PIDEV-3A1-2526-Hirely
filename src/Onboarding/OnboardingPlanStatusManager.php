<?php

namespace App\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;

final class OnboardingPlanStatusManager
{
    /**
     * @param array{total?: int, completed?: int, in_progress?: int, on_hold?: int, blocked?: int, not_started?: int} $summary
     */
    public function deriveStatusFromSummary(array $summary): string
    {
        $total = (int) ($summary['total'] ?? 0);
        $completed = (int) ($summary['completed'] ?? 0);
        $inProgress = (int) ($summary['in_progress'] ?? 0);
        $onHold = (int) ($summary['on_hold'] ?? 0);
        $blocked = (int) ($summary['blocked'] ?? 0);
        $notStarted = (int) ($summary['not_started'] ?? 0);

        if (0 === $total || $total === $notStarted) {
            return Onboardingplan::STATUS_PENDING;
        }

        if ($completed === $total) {
            return Onboardingplan::STATUS_COMPLETED;
        }

        if ($onHold > 0 || $blocked > 0) {
            return Onboardingplan::STATUS_ON_HOLD;
        }

        if ($inProgress > 0 || $completed > 0) {
            return Onboardingplan::STATUS_IN_PROGRESS;
        }

        return Onboardingplan::STATUS_PENDING;
    }

    /**
     * @param iterable<Onboardingtask> $tasks
     */
    public function deriveStatus(iterable $tasks): string
    {
        $total = 0;
        $completed = 0;
        $inProgress = 0;
        $onHoldOrBlocked = 0;
        $notStarted = 0;

        foreach ($tasks as $task) {
            ++$total;

            switch ($task->getStatus()) {
                case Onboardingtask::STATUS_COMPLETED:
                    ++$completed;
                    break;

                case Onboardingtask::STATUS_IN_PROGRESS:
                    ++$inProgress;
                    break;

                case Onboardingtask::STATUS_ON_HOLD:
                case Onboardingtask::STATUS_BLOCKED:
                    ++$onHoldOrBlocked;
                    break;

                case Onboardingtask::STATUS_NOT_STARTED:
                default:
                    ++$notStarted;
                    break;
            }
        }

        return $this->deriveStatusFromSummary([
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'on_hold' => $onHoldOrBlocked,
            'blocked' => 0,
            'not_started' => $notStarted,
        ]);
    }

    /**
     * @param iterable<Onboardingtask>|null $tasks
     */
    public function syncPlanStatus(Onboardingplan $plan, ?iterable $tasks = null): bool
    {
        $resolvedStatus = $this->deriveStatus($tasks ?? $plan->getOnboardingtasks());

        if ($plan->getStatus() === $resolvedStatus) {
            return false;
        }

        $plan->setStatus($resolvedStatus);

        return true;
    }

    /**
     * @param array{total?: int, completed?: int, in_progress?: int, on_hold?: int, blocked?: int, not_started?: int} $summary
     */
    public function syncPlanStatusFromSummary(Onboardingplan $plan, array $summary): bool
    {
        $resolvedStatus = $this->deriveStatusFromSummary($summary);

        if ($plan->getStatus() === $resolvedStatus) {
            return false;
        }

        $plan->setStatus($resolvedStatus);

        return true;
    }
}
