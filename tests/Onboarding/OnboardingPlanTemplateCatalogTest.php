<?php

namespace App\Tests\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Users;
use App\Onboarding\OnboardingPlanTemplateCatalog;
use PHPUnit\Framework\TestCase;

final class OnboardingPlanTemplateCatalogTest extends TestCase
{
    public function testCatalogExposesReusableTemplates(): void
    {
        $catalog = new OnboardingPlanTemplateCatalog();

        self::assertArrayHasKey('first_week', $catalog->all());
        self::assertArrayHasKey('Remote Arrival', $catalog->choiceMap());
    }

    public function testTemplateCreatesPlanWithTasksAndAssignedUser(): void
    {
        $catalog = new OnboardingPlanTemplateCatalog();
        $user = new Users();
        $user->setFirstName('Youssef');
        $user->setLastName('Kaddech');
        $user->setEmail('youssef@example.com');

        $plan = $catalog->createPlanFromTemplate('first_week', $user, new \DateTimeImmutable('2026-05-20'));

        self::assertInstanceOf(Onboardingplan::class, $plan);
        self::assertSame($user, $plan->getUser());
        self::assertCount(3, $plan->getOnboardingtasks());
        self::assertSame(Onboardingplan::STATUS_PENDING, $plan->getStatus());
    }

    public function testUnknownTemplateReturnsNull(): void
    {
        $catalog = new OnboardingPlanTemplateCatalog();

        self::assertNull($catalog->createPlanFromTemplate('missing-template', new Users()));
    }
}
