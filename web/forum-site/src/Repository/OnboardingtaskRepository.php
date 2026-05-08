<?php

namespace App\Repository;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Onboardingtask>
 */
class OnboardingtaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Onboardingtask::class);
    }

    /**
     * @return list<Onboardingtask>
     */
    public function findByPlanLimited(Onboardingplan $plan, int $limit = 20): array
    {
        return $this->createQueryBuilder('task')
            ->andWhere('task.plan = :plan')
            ->setParameter('plan', $plan)
            ->orderBy('task.taskId', 'DESC')
            ->setMaxResults(max(1, min($limit, 50)))
            ->getQuery()
            ->getResult();
    }

    /**
     * @param list<int> $planIds
     * @return array<int, array{total: int, completed: int, in_progress: int, blocked: int, on_hold: int, not_started: int}>
     */
    public function getStatusSummaryForPlanIds(array $planIds): array
    {
        $planIds = $this->cleanPlanIds($planIds);
        if ($planIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('task')
            ->select('IDENTITY(task.plan) AS plan_id')
            ->addSelect('COUNT(task.taskId) AS total')
            ->addSelect('SUM(CASE WHEN task.status = :completed THEN 1 ELSE 0 END) AS completed')
            ->addSelect('SUM(CASE WHEN task.status = :inProgress THEN 1 ELSE 0 END) AS in_progress')
            ->addSelect('SUM(CASE WHEN task.status = :blocked THEN 1 ELSE 0 END) AS blocked')
            ->addSelect('SUM(CASE WHEN task.status = :onHold THEN 1 ELSE 0 END) AS on_hold')
            ->addSelect('SUM(CASE WHEN task.status = :notStarted THEN 1 ELSE 0 END) AS not_started')
            ->andWhere('IDENTITY(task.plan) IN (:planIds)')
            ->setParameter('planIds', $planIds)
            ->setParameter('completed', Onboardingtask::STATUS_COMPLETED)
            ->setParameter('inProgress', Onboardingtask::STATUS_IN_PROGRESS)
            ->setParameter('blocked', Onboardingtask::STATUS_BLOCKED)
            ->setParameter('onHold', Onboardingtask::STATUS_ON_HOLD)
            ->setParameter('notStarted', Onboardingtask::STATUS_NOT_STARTED)
            ->groupBy('plan_id')
            ->getQuery()
            ->getArrayResult();

        $summaryByPlanId = [];
        foreach ($planIds as $planId) {
            $summaryByPlanId[$planId] = [
                'total' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'blocked' => 0,
                'on_hold' => 0,
                'not_started' => 0,
            ];
        }

        foreach ($rows as $row) {
            $planId = (int) ($row['plan_id'] ?? 0);
            if ($planId <= 0) {
                continue;
            }

            $summaryByPlanId[$planId] = [
                'total' => (int) ($row['total'] ?? 0),
                'completed' => (int) ($row['completed'] ?? 0),
                'in_progress' => (int) ($row['in_progress'] ?? 0),
                'blocked' => (int) ($row['blocked'] ?? 0),
                'on_hold' => (int) ($row['on_hold'] ?? 0),
                'not_started' => (int) ($row['not_started'] ?? 0),
            ];
        }

        return $summaryByPlanId;
    }

    /**
     * @param list<int> $planIds
     * @return array<int, list<Onboardingtask>>
     */
    public function findGroupedByPlanIds(array $planIds, int $limitPerPlan = 3): array
    {
        $planIds = $this->cleanPlanIds($planIds);
        if ($planIds === []) {
            return [];
        }

        $tasks = $this->createQueryBuilder('task')
            ->leftJoin('task.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('plan.planId IN (:planIds)')
            ->setParameter('planIds', $planIds)
            ->orderBy('task.taskId', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($planIds as $planId) {
            $grouped[$planId] = [];
        }

        foreach ($tasks as $task) {
            $planId = (int) $task->getPlan()?->getPlanId();
            if (!isset($grouped[$planId]) || count($grouped[$planId]) >= $limitPerPlan) {
                continue;
            }

            $grouped[$planId][] = $task;
        }

        return $grouped;
    }

    /**
     * @param list<int> $planIds
     * @return list<int>
     */
    private function cleanPlanIds(array $planIds): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $planIds), static fn (int $planId): bool => $planId > 0)));
    }
}
