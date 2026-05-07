<?php

namespace App\Tests\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Onboarding\OnboardingPlanStatusManager;
use PHPUnit\Framework\TestCase;

final class OnboardingPlanStatusManagerTest extends TestCase
{
    private OnboardingPlanStatusManager $statusManager;

    protected function setUp(): void
    {
        $this->statusManager = new OnboardingPlanStatusManager();
    }

    public function testEmptyOrNotStartedPlanStaysPending(): void
    {
        self::assertSame(Onboardingplan::STATUS_PENDING, $this->statusManager->deriveStatusFromSummary([
            'total' => 3,
            'not_started' => 3,
        ]));
    }

    public function testCompletedPlanRequiresAllTasksCompleted(): void
    {
        self::assertSame(Onboardingplan::STATUS_IN_PROGRESS, $this->statusManager->deriveStatusFromSummary([
            'total' => 4,
            'completed' => 1,
            'in_progress' => 1,
            'not_started' => 2,
        ]));

        self::assertSame(Onboardingplan::STATUS_COMPLETED, $this->statusManager->deriveStatusFromSummary([
            'total' => 4,
            'completed' => 4,
        ]));
    }

    public function testBlockedTaskMovesPlanOnHold(): void
    {
        self::assertSame(Onboardingplan::STATUS_ON_HOLD, $this->statusManager->deriveStatusFromSummary([
            'total' => 3,
            'completed' => 1,
            'blocked' => 1,
            'not_started' => 1,
        ]));
    }

    public function testSyncPlanStatusUpdatesEntityWhenTaskFlowChanges(): void
    {
        $plan = (new Onboardingplan())->setStatus(Onboardingplan::STATUS_PENDING);

        $task = (new Onboardingtask())
            ->setStatus(Onboardingtask::STATUS_IN_PROGRESS)
            ->setPlan($plan);

        self::assertTrue($this->statusManager->syncPlanStatus($plan, [$task]));
        self::assertSame(Onboardingplan::STATUS_IN_PROGRESS, $plan->getStatus());
    }
}
