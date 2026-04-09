<?php

namespace App\Repository;

use App\Entity\InterviewEvaluation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InterviewEvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InterviewEvaluation::class);
    }

    public function findPendingByRecruiter($recruiter)
    {
        return $this->createQueryBuilder('e')
            ->where('e.recruiter = :recruiter')
            ->andWhere('e.overallRating IS NULL')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByRecruiter($recruiter)
    {
        return $this->createQueryBuilder('e')
            ->where('e.recruiter = :recruiter')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
