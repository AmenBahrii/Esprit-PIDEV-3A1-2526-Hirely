<?php

namespace App\Tests\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Onboarding\OnboardingFlowService;
use PHPUnit\Framework\TestCase;

final class OnboardingFlowServiceTest extends TestCase
{
    public function testFlowDetectsHighRiskBlockedTask(): void
    {
        $plan = (new Onboardingplan())
            ->setDeadline(new \DateTime('+5 days'));

        $blockedTask = (new Onboardingtask())
            ->setTitle('Resolve access blocker')
            ->setStatus(Onboardingtask::STATUS_BLOCKED)
            ->setDeadline(new \DateTime('+1 day'));

        $flow = (new OnboardingFlowService())->buildPlanFlow($plan, [$blockedTask]);

        self::assertSame('high', $flow['riskLevel']);
        self::assertSame(0, $flow['progressPercent']);
        self::assertSame('Resolve access blocker', $flow['nextActions'][0]['title']);
        self::assertNotEmpty($flow['riskSignals']);
    }

    public function testFlowMarksCompletedPlanReadyForReview(): void
    {
        $plan = (new Onboardingplan())
            ->setDeadline(new \DateTime('+1 day'));

        $task = (new Onboardingtask())
            ->setTitle('Submit onboarding proof')
            ->setStatus(Onboardingtask::STATUS_COMPLETED)
            ->setFilePath('https://example.com/proof.pdf');

        $flow = (new OnboardingFlowService())->buildPlanFlow($plan, [$task]);

        self::assertSame('low', $flow['riskLevel']);
        self::assertSame(100, $flow['progressPercent']);
        self::assertStringContainsString('complete', strtolower($flow['smartSummary']));
    }
}
