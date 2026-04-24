<?php

namespace App\Repository;

use App\Entity\Role;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Users::class);
    }

    public function findActiveByEmail(string $email): ?Users
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.role', 'r')
            ->addSelect('r')
            ->andWhere('LOWER(TRIM(u.email)) = LOWER(TRIM(:email))')
            ->andWhere('LOWER(TRIM(u.status)) = :status')
            ->setParameter('email', $email)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOrCreateSystemUser(string $email, string $firstName, string $lastName): Users
    {
        $existing = $this->findActiveByEmail($email);
        if ($existing instanceof Users) {
            return $existing;
        }

        $entityManager = $this->getEntityManager();
        if (!$entityManager instanceof EntityManagerInterface) {
            throw new \RuntimeException('Entity manager unavailable.');
        }

        $role = $entityManager->getRepository(Role::class)->findOneBy([
            'name' => 'system',
            'status' => 'active',
        ]);

        if (!$role instanceof Role) {
            $role = $entityManager->getRepository(Role::class)->findOneBy([
                'name' => 'candidate',
                'status' => 'active',
            ]);
        }

        if (!$role instanceof Role) {
            throw new \RuntimeException('No active role available for the Gemini system user.');
        }

        $user = (new Users())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPassword('!')
            ->setRole($role)
            ->setStatus('active')
            ->setProfilePic(null)
            ->setFaceData(null)
            ->setGoogleId(null);

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }
}
