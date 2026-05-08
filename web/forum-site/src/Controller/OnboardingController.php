<?php

namespace App\Controller;

use App\Entity\Onboardingplan;
use App\Repository\OnboardingplanRepository;
use App\Repository\OnboardingtaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OnboardingController extends AbstractController
{
    #[Route('/onboarding', name: 'onboarding_index', methods: ['GET'])]
    public function index(
        Request $request,
        OnboardingplanRepository $planRepository,
        OnboardingtaskRepository $taskRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));
        $plans = [];
        $taskSummary = [];
        $tasksByPlan = [];
        $schemaReady = true;
        $schemaMessage = null;

        try {
            $plans = $planRepository->findRecentForDashboard($search !== '' ? $search : null, $status !== '' ? $status : null);
            $planIds = array_values(array_filter(array_map(
                static fn (Onboardingplan $plan): int => (int) $plan->getPlanId(),
                $plans
            )));
            $taskSummary = $taskRepository->getStatusSummaryForPlanIds($planIds);
            $tasksByPlan = $taskRepository->findGroupedByPlanIds($planIds, 3);
        } catch (\Throwable $exception) {
            $schemaReady = false;
            $schemaMessage = 'Onboarding tables are not available yet. Apply docs/integration/onboarding_runtime_schema.sql in a dev/test database, then reload this page.';
        }

        return $this->render('onboarding/index.html.twig', [
            'plans' => $plans,
            'task_summary' => $taskSummary,
            'tasks_by_plan' => $tasksByPlan,
            'schema_ready' => $schemaReady,
            'schema_message' => $schemaMessage,
            'selected_status' => $status,
            'search_term' => $search,
            'plan_status_choices' => Onboardingplan::getStatusChoices(),
        ]);
    }

    #[Route('/onboarding/plans/{id}', name: 'onboarding_plan_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        OnboardingplanRepository $planRepository,
        OnboardingtaskRepository $taskRepository,
    ): Response {
        $schemaReady = true;
        $schemaMessage = null;
        $plan = null;
        $tasks = [];
        $taskSummary = [];

        try {
            $plan = $planRepository->find($id);
            if (!$plan instanceof Onboardingplan) {
                throw $this->createNotFoundException('Onboarding plan not found.');
            }

            $tasks = $taskRepository->findByPlanLimited($plan, 30);
            $taskSummary = $taskRepository->getStatusSummaryForPlanIds([(int) $plan->getPlanId()]);
        } catch (\Throwable $exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                throw $exception;
            }

            $schemaReady = false;
            $schemaMessage = 'Onboarding tables are not available yet. Apply docs/integration/onboarding_runtime_schema.sql in a dev/test database, then reload this page.';
        }

        return $this->render('onboarding/show.html.twig', [
            'plan' => $plan,
            'tasks' => $tasks,
            'task_summary' => $taskSummary[(int) $plan?->getPlanId()] ?? null,
            'schema_ready' => $schemaReady,
            'schema_message' => $schemaMessage,
        ]);
    }
}
