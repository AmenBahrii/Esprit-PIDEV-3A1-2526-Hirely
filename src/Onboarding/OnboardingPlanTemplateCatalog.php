<?php

namespace App\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Entity\Users;

final class OnboardingPlanTemplateCatalog
{
    /**
     * @return array<string, array{
     *     key: string,
     *     name: string,
     *     summary: string,
     *     accent: string,
     *     audience: string,
     *     estimated_duration: string,
     *     tasks: list<array{
     *         title: string,
     *         description: string,
     *         days_before_deadline: int
     *     }>
     * }>
     */
    public function all(): array
    {
        return [
            'first_week' => [
                'key' => 'first_week',
                'name' => 'First Week Launch',
                'summary' => 'Best for a standard new hire flow with compliance, equipment pickup, and manager kickoff.',
                'accent' => 'orange',
                'audience' => 'General onboarding',
                'estimated_duration' => '1 week',
                'tasks' => [
                    [
                        'title' => 'Complete profile and HR documents',
                        'description' => 'Review the onboarding instructions, confirm personal details, and submit the required HR paperwork.',
                        'days_before_deadline' => 6,
                    ],
                    [
                        'title' => 'Collect equipment and workspace access',
                        'description' => 'Pick up the laptop, badge, and workspace access items, then confirm everything is ready to use.',
                        'days_before_deadline' => 3,
                    ],
                    [
                        'title' => 'Attend team kickoff meeting',
                        'description' => 'Join the first alignment meeting with the manager to review expectations, tools, and first priorities.',
                        'days_before_deadline' => 0,
                    ],
                ],
            ],
            'developer_ramp' => [
                'key' => 'developer_ramp',
                'name' => 'Developer Ramp-Up',
                'summary' => 'Focused on engineering onboarding with repository access, environment setup, and first delivery readiness.',
                'accent' => 'violet',
                'audience' => 'Engineering hires',
                'estimated_duration' => '10 days',
                'tasks' => [
                    [
                        'title' => 'Get engineering access approved',
                        'description' => 'Grant repository, issue tracker, VPN, and communication tool access so the developer can start safely.',
                        'days_before_deadline' => 8,
                    ],
                    [
                        'title' => 'Set up the local development environment',
                        'description' => 'Install project dependencies, run the application locally, and validate that the core tooling is working.',
                        'days_before_deadline' => 4,
                    ],
                    [
                        'title' => 'Deliver the first guided task',
                        'description' => 'Complete a small starter ticket with mentor review to confirm the developer is fully operational.',
                        'days_before_deadline' => 0,
                    ],
                ],
            ],
            'remote_arrival' => [
                'key' => 'remote_arrival',
                'name' => 'Remote Arrival',
                'summary' => 'Designed for remote hires with document validation, device shipment, and virtual onboarding checkpoints.',
                'accent' => 'blue',
                'audience' => 'Remote onboarding',
                'estimated_duration' => '2 weeks',
                'tasks' => [
                    [
                        'title' => 'Confirm remote onboarding documents',
                        'description' => 'Validate signed contracts, identity documents, and shipping information before the onboarding starts.',
                        'days_before_deadline' => 10,
                    ],
                    [
                        'title' => 'Track device delivery and access setup',
                        'description' => 'Monitor the equipment shipment and prepare account credentials so the new hire can log in immediately.',
                        'days_before_deadline' => 5,
                    ],
                    [
                        'title' => 'Run the virtual welcome session',
                        'description' => 'Host the remote welcome call, review communication channels, and confirm the first-week milestones.',
                        'days_before_deadline' => 0,
                    ],
                ],
            ],
            'compliance_fast_track' => [
                'key' => 'compliance_fast_track',
                'name' => 'Compliance Fast-Track',
                'summary' => 'A tighter workflow for regulated roles where approvals, policy review, and proof collection matter most.',
                'accent' => 'emerald',
                'audience' => 'Sensitive or regulated roles',
                'estimated_duration' => '5 days',
                'tasks' => [
                    [
                        'title' => 'Validate mandatory documents',
                        'description' => 'Review NDA, policy acknowledgement, and role-specific compliance documents before activation.',
                        'days_before_deadline' => 4,
                    ],
                    [
                        'title' => 'Complete security and policy briefing',
                        'description' => 'Walk through internal rules, information handling expectations, and incident reporting guidance.',
                        'days_before_deadline' => 2,
                    ],
                    [
                        'title' => 'Approve activation checklist',
                        'description' => 'Confirm that all evidence has been collected and that the role is ready to move into active delivery.',
                        'days_before_deadline' => 0,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function choiceMap(): array
    {
        $choices = [];

        foreach ($this->all() as $template) {
            $choices[$template['name']] = $template['key'];
        }

        return $choices;
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     summary: string,
     *     accent: string,
     *     audience: string,
     *     estimated_duration: string,
     *     tasks: list<array{title: string, description: string, days_before_deadline: int}>
     * }|null
     */
    public function find(string $key): ?array
    {
        return $this->all()[$key] ?? null;
    }

    public function createPlanFromTemplate(string $key, Users $user, ?\DateTimeInterface $deadline = null): ?Onboardingplan
    {
        $template = $this->find($key);
        if (null === $template) {
            return null;
        }

        $planDeadline = $deadline ? \DateTime::createFromInterface($deadline)->setTime(0, 0) : null;
        $plan = (new Onboardingplan())
            ->setUser($user)
            ->setDeadline($planDeadline)
            ->setStatus(Onboardingplan::STATUS_PENDING);

        $referenceDeadline = $planDeadline ? clone $planDeadline : null;
        $today = new \DateTime('today');

        foreach ($template['tasks'] as $taskDefinition) {
            $task = (new Onboardingtask())
                ->setTitle($taskDefinition['title'])
                ->setDescription($taskDefinition['description'])
                ->setStatus(Onboardingtask::STATUS_NOT_STARTED);

            if ($referenceDeadline instanceof \DateTime) {
                $taskDeadline = (clone $referenceDeadline)->modify(sprintf('-%d days', max(0, (int) $taskDefinition['days_before_deadline'])));
                if ($taskDeadline < $today) {
                    $taskDeadline = clone $today;
                }
                $task->setDeadline($taskDeadline);
            }

            $plan->addOnboardingtask($task);
        }

        return $plan;
    }
}
