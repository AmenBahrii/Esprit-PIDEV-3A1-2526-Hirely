<?php

namespace App\Tests\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Entity\User;
use App\Onboarding\OnboardingPlanTemplateCatalog;
use PHPUnit\Framework\TestCase;

final class OnboardingPlanTemplateCatalogTest extends TestCase
{
    private OnboardingPlanTemplateCatalog $catalog;

    protected function setUp(): void
    {
        $this->catalog = new OnboardingPlanTemplateCatalog();
    }

    public function testChoiceMapExposesEveryReusablePlanTemplate(): void
    {
        $choices = $this->catalog->choiceMap();

        self::assertSame('first_week', $choices['First Week Launch']);
        self::assertSame('developer_ramp', $choices['Developer Ramp-Up']);
        self::assertSame('remote_arrival', $choices['Remote Arrival']);
        self::assertSame('compliance_fast_track', $choices['Compliance Fast-Track']);
    }

    public function testUnknownTemplateCannotCreateAPlan(): void
    {
        self::assertNull($this->catalog->createPlanFromTemplate('unknown_template', new User()));
    }

    public function testTemplateCreatesPendingPlanWithLinkedTasksAndSafeDeadlines(): void
    {
        $user = (new User())
            ->setFirst_name('Youssef')
            ->setLast_name('Kaddech')
            ->setEmail('youssef.kaddech@hirely.local');
        $deadline = new \DateTimeImmutable('+10 days');

        $plan = $this->catalog->createPlanFromTemplate('developer_ramp', $user, $deadline);

        self::assertInstanceOf(Onboardingplan::class, $plan);
        self::assertSame($user, $plan->getUser());
        self::assertSame(Onboardingplan::STATUS_PENDING, $plan->getStatus());
        self::assertEquals($deadline->format('Y-m-d'), $plan->getDeadline()?->format('Y-m-d'));
        self::assertCount(3, $plan->getOnboardingtasks());

        $today = new \DateTimeImmutable('today');
        foreach ($plan->getOnboardingtasks() as $task) {
            self::assertInstanceOf(Onboardingtask::class, $task);
            self::assertSame($plan, $task->getPlan());
            self::assertSame(Onboardingtask::STATUS_NOT_STARTED, $task->getStatus());
            self::assertNotEmpty($task->getTitle());
            self::assertNotEmpty($task->getDescription());
            self::assertNotNull($task->getDeadline());
            self::assertGreaterThanOrEqual($today->format('Y-m-d'), $task->getDeadline()->format('Y-m-d'));
            self::assertLessThanOrEqual($deadline->format('Y-m-d'), $task->getDeadline()->format('Y-m-d'));
        }
    }
}
