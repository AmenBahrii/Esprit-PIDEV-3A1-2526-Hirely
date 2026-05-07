<?php

namespace App\Onboarding;

use App\Entity\Onboardingplan;
use App\Entity\Onboardingtask;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class ViewerContext
{
    public const ROLE_ADMIN = 1;
    public const ROLE_RECRUITER = 2;
    public const ROLE_CANDIDATE = 3;

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getCurrentUser(): ?Users
    {
        $user = $this->security->getUser();

        return $user instanceof Users ? $user : null;
    }

    /**
     * @return Users[]
     */
    public function getAvailableUsers(): array
    {
        return $this->entityManager
            ->getRepository(Users::class)
            ->createQueryBuilder('user')
            ->leftJoin('user.role', 'role')
            ->addSelect('role')
            ->andWhere('LOWER(role.name) IN (:roles)')
            ->setParameter('roles', ['admin', 'recruiter', 'candidate'])
            ->orderBy('user.first_name', 'ASC')
            ->addOrderBy('user.last_name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function setViewerUserId(?int $viewerUserId): bool
    {
        return false;
    }

    public function getRoleName(): string
    {
        $roleName = $this->normalizedRoleName();

        return '' !== $roleName ? ucfirst($roleName) : 'Guest';
    }

    public function getRoleBadgeClass(): string
    {
        return match ($this->normalizedRoleName()) {
            'admin' => 'viewer-badge admin',
            'recruiter' => 'viewer-badge recruiter',
            'candidate' => 'viewer-badge candidate',
            default => 'viewer-badge guest',
        };
    }

    public function getWorkspaceTitle(): string
    {
        return $this->isCandidate() ? 'My Onboarding Space' : 'Onboarding Coordination Workspace';
    }

    public function getSidebarTitle(): string
    {
        return $this->isCandidate() ? 'Candidate Workspace' : 'Admin Panel';
    }

    public function getCurrentUserDisplayName(): string
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            return 'No active user';
        }

        return trim(($user->getFirstName() ?? '') . ' ' . ($user->getLastName() ?? ''));
    }

    public function isAdminOrRecruiter(): bool
    {
        return \in_array($this->normalizedRoleName(), ['admin', 'recruiter'], true);
    }

    public function isAdmin(): bool
    {
        return 'admin' === $this->normalizedRoleName();
    }

    public function isRecruiter(): bool
    {
        return 'recruiter' === $this->normalizedRoleName();
    }

    public function isCandidate(): bool
    {
        return 'candidate' === $this->normalizedRoleName();
    }

    public function canCreatePlans(): bool
    {
        return $this->isAdminOrRecruiter();
    }

    public function canDeletePlan(Onboardingplan $plan): bool
    {
        return $this->isAdminOrRecruiter() && $this->canViewPlan($plan);
    }

    public function canViewPlan(?Onboardingplan $plan): bool
    {
        if (!$plan) {
            return false;
        }

        if ($this->isAdminOrRecruiter()) {
            return true;
        }

        return $this->isCandidate() && $this->belongsToCurrentUser($plan->getUser());
    }

    public function canEditPlan(Onboardingplan $plan): bool
    {
        return $this->isAdminOrRecruiter() && $this->canViewPlan($plan);
    }

    public function canFullyEditPlan(Onboardingplan $plan): bool
    {
        return $this->canEditPlan($plan);
    }

    public function canCreateTasks(): bool
    {
        return $this->isAdminOrRecruiter();
    }

    public function canViewTask(Onboardingtask $task): bool
    {
        return $this->canViewPlan($task->getPlan());
    }

    public function canEditTask(Onboardingtask $task): bool
    {
        return $this->isAdminOrRecruiter() || ($this->isCandidate() && $this->canViewTask($task));
    }

    public function canFullyEditTask(Onboardingtask $task): bool
    {
        return $this->isAdminOrRecruiter() && $this->canViewTask($task);
    }

    public function canDeleteTask(Onboardingtask $task): bool
    {
        return $this->isAdminOrRecruiter() && $this->canViewTask($task);
    }

    public function canViewQr(Onboardingplan $plan): bool
    {
        return $this->canViewPlan($plan);
    }

    private function belongsToCurrentUser(?Users $user): bool
    {
        $currentUser = $this->getCurrentUser();

        return null !== $user && null !== $currentUser && $user->getId() === $currentUser->getId();
    }

    private function normalizedRoleName(): string
    {
        return strtolower((string) $this->getCurrentUser()?->getRole()?->getName());
    }
}
