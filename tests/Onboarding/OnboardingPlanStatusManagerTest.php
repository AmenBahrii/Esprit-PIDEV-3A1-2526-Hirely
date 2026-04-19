<?php

namespace App\Tests\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Onboarding\OnboardingPlanStatusManager;
use PHPUnit\Framework\TestCase;

class OnboardingPlanStatusManagerTest extends TestCase
{
    private OnboardingPlanStatusManager $statusManager;

    protected function setUp(): void
    {
        $this->statusManager = new OnboardingPlanStatusManager();
    }

    public function testPlanWithoutTasksStaysPending(): void
    {
        self::assertSame(Onboardingplan::STATUS_PENDING, $this->statusManager->deriveStatus([]));
    }

    public function testCompletedTasksMarkPlanAsCompleted(): void
    {
        $tasks = [
            $this->taskWithStatus(Onboardingtask::STATUS_COMPLETED),
            $this->taskWithStatus(Onboardingtask::STATUS_COMPLETED),
        ];

        self::assertSame(Onboardingplan::STATUS_COMPLETED, $this->statusManager->deriveStatus($tasks));
    }

    public function testBlockedOrOnHoldTasksPutPlanOnHold(): void
    {
        $tasks = [
            $this->taskWithStatus(Onboardingtask::STATUS_IN_PROGRESS),
            $this->taskWithStatus(Onboardingtask::STATUS_BLOCKED),
        ];

        self::assertSame(Onboardingplan::STATUS_ON_HOLD, $this->statusManager->deriveStatus($tasks));
    }

    public function testMixedProgressMarksPlanInProgress(): void
    {
        $tasks = [
            $this->taskWithStatus(Onboardingtask::STATUS_COMPLETED),
            $this->taskWithStatus(Onboardingtask::STATUS_NOT_STARTED),
        ];

        self::assertSame(Onboardingplan::STATUS_IN_PROGRESS, $this->statusManager->deriveStatus($tasks));
    }

    public function testSyncPlanStatusUpdatesStoredStatus(): void
    {
        $plan = new Onboardingplan();
        $plan->setStatus(Onboardingplan::STATUS_PENDING);

        $wasUpdated = $this->statusManager->syncPlanStatus($plan, [
            $this->taskWithStatus(Onboardingtask::STATUS_IN_PROGRESS),
        ]);

        self::assertTrue($wasUpdated);
        self::assertSame(Onboardingplan::STATUS_IN_PROGRESS, $plan->getStatus());
    }

    public function testSummaryWithMixedStatusesDoesNotMarkPlanCompleted(): void
    {
        $status = $this->statusManager->deriveStatusFromSummary([
            'total' => 4,
            'completed' => 1,
            'in_progress' => 1,
            'blocked' => 1,
            'on_hold' => 0,
            'not_started' => 1,
        ]);

        self::assertSame(Onboardingplan::STATUS_ON_HOLD, $status);
    }

    private function taskWithStatus(string $status): Onboardingtask
    {
        $task = new Onboardingtask();
        $task->setStatus($status);

        return $task;
    }
}
