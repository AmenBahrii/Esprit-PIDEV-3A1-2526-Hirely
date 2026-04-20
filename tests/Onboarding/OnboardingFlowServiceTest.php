<?php

namespace App\Tests\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Onboarding\OnboardingFlowService;
use PHPUnit\Framework\TestCase;

class OnboardingFlowServiceTest extends TestCase
{
    private OnboardingFlowService $flowService;

    protected function setUp(): void
    {
        $this->flowService = new OnboardingFlowService();
    }

    public function testBlockedAndOverdueTasksRaiseHighRiskFlow(): void
    {
        $plan = new Onboardingplan();
        $plan->setPlanId(1);
        $plan->setDeadline(new \DateTimeImmutable('+5 days'));

        $blocked = $this->task(11, 'Submit payroll details', Onboardingtask::STATUS_BLOCKED, new \DateTimeImmutable('-1 day'));
        $inProgress = $this->task(12, 'Confirm welcome meeting', Onboardingtask::STATUS_IN_PROGRESS, new \DateTimeImmutable('+1 day'));

        $flow = $this->flowService->buildPlanFlow($plan, [$blocked, $inProgress]);

        self::assertSame('high', $flow['riskLevel']);
        self::assertSame('first_day', $flow['currentPhase']['code']);
        self::assertNotEmpty($flow['riskSignals']);
        self::assertSame(11, $flow['nextActions'][0]['taskId']);
    }

    public function testCompletedPlanMovesToCompletionPhase(): void
    {
        $plan = new Onboardingplan();
        $plan->setPlanId(2);
        $plan->setDeadline(new \DateTimeImmutable('+3 days'));

        $taskOne = $this->task(21, 'Upload ID', Onboardingtask::STATUS_COMPLETED, new \DateTimeImmutable('-2 days'), true);
        $taskTwo = $this->task(22, 'Sign contract', Onboardingtask::STATUS_COMPLETED, new \DateTimeImmutable('-1 day'), true);

        $flow = $this->flowService->buildPlanFlow($plan, [$taskOne, $taskTwo]);

        self::assertSame(100, $flow['progressPercent']);
        self::assertSame('low', $flow['riskLevel']);
        self::assertSame('completion', $flow['currentPhase']['code']);
        self::assertSame('The flow is complete and ready for final review.', $flow['smartSummary']);
    }

    public function testMissingAttachmentsCreateMediumRiskSignal(): void
    {
        $plan = new Onboardingplan();
        $plan->setPlanId(3);
        $plan->setDeadline(new \DateTimeImmutable('+8 days'));

        $completedWithoutProof = $this->task(31, 'Upload signed NDA', Onboardingtask::STATUS_COMPLETED, new \DateTimeImmutable('+1 day'));
        $notStarted = $this->task(32, 'Choose equipment slot', Onboardingtask::STATUS_NOT_STARTED, new \DateTimeImmutable('+6 days'));

        $flow = $this->flowService->buildPlanFlow($plan, [$completedWithoutProof, $notStarted]);

        self::assertSame('medium', $flow['riskLevel']);
        self::assertSame('missing_proof', $flow['riskSignals'][0]['code']);
        self::assertStringContainsString('proof attachments', $flow['smartSummary']);
    }

    private function task(
        int $id,
        string $title,
        string $status,
        \DateTimeInterface $deadline,
        bool $withAttachment = false,
    ): Onboardingtask {
        $task = new Onboardingtask();
        $task->setTaskId($id);
        $task->setTitle($title);
        $task->setStatus($status);
        $task->setDeadline($deadline);

        if ($withAttachment) {
            $task->setFilePath('https://example.com/file.pdf');
            $task->setOriginalFileName('file.pdf');
        }

        return $task;
    }
}
