<?php

namespace App\Repository;

use App\Entity\Interview;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InterviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Interview::class);
    }

    public function findUpcomingByRecruiter($recruiter)
    {
        return $this->createQueryBuilder('i')
            ->where('i.recruiter = :recruiter')
            ->andWhere('i.status = :status')
            ->andWhere('i.scheduleDate >= CURRENT_TIMESTAMP()')
            ->setParameter('recruiter', $recruiter)
            ->setParameter('status', 'scheduled')
            ->orderBy('i.scheduleDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTodayInterviews($recruiter)
    {
        return $this->createQueryBuilder('i')
            ->where('i.recruiter = :recruiter')
            ->andWhere('DATE(i.scheduleDate) = CURRENT_DATE()')
            ->setParameter('recruiter', $recruiter)
            ->orderBy('i.scheduleDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(string $status)
    {
        return $this->findBy(['status' => $status]);
    }
}
