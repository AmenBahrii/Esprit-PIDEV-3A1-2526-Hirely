<?php

namespace App\Repository;

use App\Entity\User;
use App\Onboarding\ViewerContext;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[]
     */
    public function findSelectableOnboardingViewers(): array
    {
        return $this->createQueryBuilder('user')
            ->leftJoin('user.role', 'role')
            ->addSelect('role')
            ->andWhere('role.role_id IN (:roleIds)')
            ->setParameter('roleIds', [
                ViewerContext::ROLE_CANDIDATE,
                ViewerContext::ROLE_RECRUITER,
                ViewerContext::ROLE_ADMIN,
            ])
            ->orderBy('role.role_id', 'ASC')
            ->addOrderBy('user.user_id', 'ASC')
            ->setMaxResults(30)
            ->getQuery()
            ->getResult();
    }
}
