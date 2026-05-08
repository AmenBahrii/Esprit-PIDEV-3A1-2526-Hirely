<?php

namespace App\Repository;

use App\Entity\Onboardingplan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Onboardingplan>
 */
class OnboardingplanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Onboardingplan::class);
    }

    /**
     * @return list<Onboardingplan>
     */
    public function findRecentForDashboard(?string $search = null, ?string $status = null, int $limit = 12): array
    {
        $builder = $this->createQueryBuilder('plan')
            ->leftJoin('plan.user', 'owner')
            ->addSelect('owner')
            ->orderBy('plan.planId', 'DESC')
            ->setMaxResults(max(1, min($limit, 50)));

        $search = $search !== null ? trim($search) : '';
        if ($search !== '') {
            $builder
                ->andWhere('LOWER(COALESCE(owner.firstName, \'\')) LIKE :search OR LOWER(COALESCE(owner.lastName, \'\')) LIKE :search OR LOWER(COALESCE(owner.email, \'\')) LIKE :search OR LOWER(plan.status) LIKE :search OR LOWER(COALESCE(plan.qrToken, \'\')) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $status = $status !== null ? trim($status) : '';
        if ($status !== '') {
            $builder
                ->andWhere('plan.status = :status')
                ->setParameter('status', $status);
        }

        return $builder->getQuery()->getResult();
    }
}
