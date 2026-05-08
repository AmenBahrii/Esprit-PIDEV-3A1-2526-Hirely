<?php

namespace App\Tests\Service;

use App\Entity\Role;
use App\Entity\Users;
use App\Service\UsersManager;
use PHPUnit\Framework\TestCase;

class UsersManagerTest extends TestCase
{
    public function testValidLocalUser(): void
    {
        $user = $this->createValidLocalUser();

        $manager = new UsersManager();

        $this->assertTrue($manager->validate($user));
    }

    public function testLocalUserWithoutPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password is required for a local account');

        $user = $this->createValidLocalUser();
        $user->setPassword('');

        $manager = new UsersManager();
        $manager->validate($user);
    }

    public function testLocalUserWithShortPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must contain at least 6 characters');

        $user = $this->createValidLocalUser();
        $user->setPassword('123');

        $manager = new UsersManager();
        $manager->validate($user);
    }

    public function testLocalUserWithSixCharacterPassword(): void
    {
        $user = $this->createValidLocalUser();
        $user->setPassword('123456');

        $manager = new UsersManager();

        $this->assertTrue($manager->validate($user));
    }

    public function testUserWithInactiveRole(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Inactive roles cannot be assigned to a user');

        $user = $this->createValidLocalUser();
        $user->setRole($this->createRole('candidate', 'inactive'));

        $manager = new UsersManager();
        $manager->validate($user);
    }

    public function testGoogleLinkedUserWithoutPassword(): void
    {
        $user = $this->createValidLocalUser();
        $user->setPassword('');
        $user->setGoogleId('google-account-123');

        $manager = new UsersManager();

        $this->assertTrue($manager->validate($user));
    }

    public function testGoogleLinkedUserWithInactiveRoleStillRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Inactive roles cannot be assigned to a user');

        $user = $this->createValidLocalUser();
        $user->setPassword('');
        $user->setGoogleId('google-account-456');
        $user->setRole($this->createRole('candidate', 'inactive'));

        $manager = new UsersManager();
        $manager->validate($user);
    }

    private function createValidLocalUser(): Users
    {
        $user = new Users();
        $user->setFirstName('Amine');
        $user->setLastName('Ben Salem');
        $user->setEmail('amine@example.com');
        $user->setPassword('secret123');
        $user->setStatus('active');
        $user->setRole($this->createRole('candidate', 'active'));

        return $user;
    }

    private function createRole(string $name, string $status): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setDescription('Default description');
        $role->setStatus($status);
        $role->setDefaultDashboard('dashboard');

        return $role;
    }
}
