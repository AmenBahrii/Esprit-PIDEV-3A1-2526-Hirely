<?php

namespace App\Repository;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
     * @return Onboardingtask[]
     */
    public function findByPlan(Onboardingplan $plan, ?string $search = null, bool $caseSensitive = false, array $filters = []): array
    {
        $builder = $this->createQueryBuilder('task')
            ->andWhere('task.plan = :plan')
            ->setParameter('plan', $plan)
            ->orderBy('task.taskId', 'DESC');

        $this->applySearch($builder, $search, $caseSensitive);
        $this->applyFilters($builder, $filters);
        $this->applySorting($builder, (string) ($filters['sort'] ?? 'newest'));

        return $builder->getQuery()->getResult();
    }

    /**
     * @return Onboardingtask[]
     */
    public function findVisibleFor(User $viewer, ?string $search = null, bool $caseSensitive = false, array $filters = []): array
    {
        $builder = $this->createQueryBuilder('task')
            ->leftJoin('task.plan', 'plan')
            ->leftJoin('plan.user', 'user')
            ->addSelect('plan', 'user')
            ->orderBy('task.taskId', 'DESC');

        if (1 === $viewer->getRole()?->getRoleId()) {
            $builder
                ->andWhere('plan.user = :viewer')
                ->setParameter('viewer', $viewer);
        }

        $this->applySearch($builder, $search, $caseSensitive);
        $this->applyFilters($builder, $filters);
        $this->applySorting($builder, (string) ($filters['sort'] ?? 'newest'));

        return $builder->getQuery()->getResult();
    }

    /**
     * @param int[] $planIds
     * @return array<int, array{total: int, completed: int, in_progress: int, blocked: int, on_hold: int, not_started: int}>
     */
    public function getStatusSummaryForPlanIds(array $planIds): array
    {
        $planIds = array_values(array_unique(array_filter(array_map('intval', $planIds), static fn (int $planId): bool => $planId > 0)));
        if ([] === $planIds) {
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
            ->andWhere('task.plan IN (:planIds)')
            ->setParameter('planIds', $planIds)
            ->setParameter('completed', Onboardingtask::STATUS_COMPLETED)
            ->setParameter('inProgress', Onboardingtask::STATUS_IN_PROGRESS)
            ->setParameter('blocked', Onboardingtask::STATUS_BLOCKED)
            ->setParameter('onHold', Onboardingtask::STATUS_ON_HOLD)
            ->setParameter('notStarted', Onboardingtask::STATUS_NOT_STARTED)
            ->groupBy('task.plan')
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
     * @param int[] $planIds
     * @return array<int, Onboardingtask[]>
     */
    public function findGroupedByPlanIds(array $planIds): array
    {
        $planIds = array_values(array_unique(array_filter(array_map('intval', $planIds), static fn (int $planId): bool => $planId > 0)));
        if ([] === $planIds) {
            return [];
        }

        $tasks = $this->createQueryBuilder('task')
            ->leftJoin('task.plan', 'plan')
            ->addSelect('plan')
            ->andWhere('task.plan IN (:planIds)')
            ->setParameter('planIds', $planIds)
            ->orderBy('task.taskId', 'DESC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($planIds as $planId) {
            $grouped[$planId] = [];
        }

        foreach ($tasks as $task) {
            $grouped[(int) $task->getPlan()?->getPlanId()][] = $task;
        }

        return $grouped;
    }

    private function applySearch(QueryBuilder $builder, ?string $search, bool $caseSensitive): void
    {
        $search = null !== $search ? trim($search) : '';

        if ('' === $search) {
            return;
        }

        $tokens = preg_split('/\s+/', $search) ?: [];

        foreach ($tokens as $index => $token) {
            $parameterName = 'search_' . $index;
            $searchTerm = '%' . ($caseSensitive ? $token : mb_strtolower($token)) . '%';
            $conditions = [
                $this->buildLikeCondition('task.title', $caseSensitive, $parameterName),
                $this->buildLikeCondition('task.status', $caseSensitive, $parameterName),
                $this->buildLikeCondition('task.original_file_name', $caseSensitive, $parameterName),
                $this->buildLikeCondition('task.content_type', $caseSensitive, $parameterName),
            ];

            if (ctype_digit($token)) {
                $conditions[] = 'task.taskId = :taskId_' . $index;
                $builder->setParameter('taskId_' . $index, (int) $token);
            }

            $builder
                ->andWhere('(' . implode(' OR ', $conditions) . ')')
                ->setParameter($parameterName, $searchTerm);
        }
    }

    private function buildLikeCondition(string $field, bool $caseSensitive, string $parameterName): string
    {
        if ($caseSensitive) {
            return sprintf('COALESCE(%s, \'\') LIKE :%s', $field, $parameterName);
        }

        return sprintf('LOWER(COALESCE(%s, \'\')) LIKE :%s', $field, $parameterName);
    }

    private function applyFilters(QueryBuilder $builder, array $filters): void
    {
        $status = trim((string) ($filters['status'] ?? ''));
        if ('' !== $status) {
            $builder
                ->andWhere('task.status = :taskStatus')
                ->setParameter('taskStatus', $status);
        }

        if (!empty($filters['attachment_only'])) {
            $builder
                ->andWhere('task.filePath IS NOT NULL')
                ->andWhere('task.filePath != :emptyFilePath')
                ->setParameter('emptyFilePath', '');
        }
    }

    private function applySorting(QueryBuilder $builder, string $sort): void
    {
        switch ($sort) {
            case 'title':
                $builder
                    ->resetDQLPart('orderBy')
                    ->addOrderBy('task.title', 'ASC')
                    ->addOrderBy('task.taskId', 'DESC');
                break;

            case 'status':
                $builder
                    ->resetDQLPart('orderBy')
                    ->addOrderBy('task.status', 'ASC')
                    ->addOrderBy('task.taskId', 'DESC');
                break;

            case 'deadline':
                $builder
                    ->resetDQLPart('orderBy')
                    ->addOrderBy('CASE WHEN task.deadline IS NULL THEN 1 ELSE 0 END', 'ASC')
                    ->addOrderBy('task.deadline', 'ASC')
                    ->addOrderBy('task.taskId', 'DESC');
                break;

            case 'newest':
            default:
                $builder
                    ->resetDQLPart('orderBy')
                    ->addOrderBy('task.taskId', 'DESC');
                break;
        }
    }

    //    /**
    //     * @return Onboardingtask[] Returns an array of Onboardingtask objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Onboardingtask
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
