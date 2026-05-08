<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r')
            ->andWhere('LOWER(TRIM(u.email)) = LOWER(TRIM(:email))')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrCreateSystemUser(string $email, string $firstName, string $lastName): User
    {
        $existing = $this->findActiveByEmail($email);
        if ($existing instanceof User) {
            return $existing;
        }

        $entityManager = $this->getEntityManager();

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPassword('!')
            ->setStatus('active')
            ->setIsVerified(true);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
