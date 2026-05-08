<?php

namespace App\Service;

use App\Entity\Users;

class UsersManager
{
    public function validate(Users $user): bool
    {
        if ($user->getGoogleId() === null && empty($user->getPassword())) {
            throw new \InvalidArgumentException('Password is required for a local account');
        }

        if ($user->getGoogleId() === null && strlen((string) $user->getPassword()) < 6) {
            throw new \InvalidArgumentException('Password must contain at least 6 characters');
        }

        if ($user->getRole() !== null && $user->getRole()->getStatus() !== 'active') {
            throw new \InvalidArgumentException('Inactive roles cannot be assigned to a user');
        }

        return true;
    }
}
