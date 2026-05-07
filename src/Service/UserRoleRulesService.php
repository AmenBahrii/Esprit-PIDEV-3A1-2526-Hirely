<?php

namespace App\Service;

use App\Entity\Role;
use App\Entity\Users;

class UserRoleRulesService
{
    public function assertRoleCanBeAssigned(Role $role): bool
    {
        if (strtolower((string) $role->getStatus()) !== 'active') {
            throw new \InvalidArgumentException('Only active roles can be assigned to users.');
        }

        return true;
    }

    public function assertActiveRoleHasDashboard(Role $role): bool
    {
        if (
            strtolower((string) $role->getStatus()) === 'active'
            && trim((string) $role->getDefaultDashboard()) === ''
        ) {
            throw new \InvalidArgumentException('Active roles must define a default dashboard.');
        }

        return true;
    }

    public function assertCredentialsReady(Users $user, ?string $plainPassword): bool
    {
        $googleId = trim((string) $user->getGoogleId());
        $plainPassword = $plainPassword === null ? null : trim($plainPassword);
        $storedPassword = trim((string) $user->getPassword());

        if ($googleId !== '' && ($plainPassword === null || $plainPassword === '')) {
            return true;
        }

        if ($plainPassword === null || $plainPassword === '') {
            if ($storedPassword === '') {
                throw new \InvalidArgumentException('A non-Google account must define a password.');
            }

            return true;
        }

        if (mb_strlen($plainPassword) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters long.');
        }

        return true;
    }

    public function canEditUser(Users $actor, Users $subject, bool $isAdmin): bool
    {
        return $isAdmin || $actor->getId() === $subject->getId();
    }
}
