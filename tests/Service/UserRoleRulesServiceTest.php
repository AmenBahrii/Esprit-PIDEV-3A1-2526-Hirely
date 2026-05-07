<?php

namespace App\Tests\Service;

use App\Entity\Role;
use App\Entity\Users;
use App\Service\UserRoleRulesService;
use PHPUnit\Framework\TestCase;

class UserRoleRulesServiceTest extends TestCase
{
    private UserRoleRulesService $service;

    protected function setUp(): void
    {
        $this->service = new UserRoleRulesService();
    }

    public function testActiveRoleCanBeAssigned(): void
    {
        $role = $this->buildRole('candidate', 'active', 'candidate_dashboard');

        self::assertTrue($this->service->assertRoleCanBeAssigned($role));
    }

    public function testInactiveRoleCannotBeAssigned(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only active roles can be assigned to users.');

        $role = $this->buildRole('recruiter', 'inactive', 'recruiter_dashboard');
        $this->service->assertRoleCanBeAssigned($role);
    }

    public function testActiveRoleNeedsDashboard(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Active roles must define a default dashboard.');

        $role = $this->buildRole('admin', 'active', null);
        $this->service->assertActiveRoleHasDashboard($role);
    }

    public function testGoogleLinkedUserCanSkipPassword(): void
    {
        $user = $this->buildUser(10, 'candidate@example.com', null, 'google-123');

        self::assertTrue($this->service->assertCredentialsReady($user, ''));
    }

    public function testLocalUserNeedsPassword(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A non-Google account must define a password.');

        $user = $this->buildUser(11, 'local@example.com');
        $this->service->assertCredentialsReady($user, '');
    }

    public function testShortPasswordIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Password must be at least 6 characters long.');

        $user = $this->buildUser(12, 'candidate2@example.com');
        $this->service->assertCredentialsReady($user, '123');
    }

    public function testAdminCanEditAnotherUser(): void
    {
        $actor = $this->buildUser(1, 'admin@example.com');
        $subject = $this->buildUser(2, 'candidate@example.com');

        self::assertTrue($this->service->canEditUser($actor, $subject, true));
    }

    private function buildRole(string $name, string $status, ?string $dashboard): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setDescription('Role description for tests');
        $role->setStatus($status);
        $role->setDefaultDashboard($dashboard);

        return $role;
    }

    private function buildUser(int $id, string $email, ?string $password = null, ?string $googleId = null): Users
    {
        $role = $this->buildRole('candidate', 'active', 'candidate_dashboard');

        $user = new Users();
        $user->setId($id);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setEmail($email);
        $user->setStatus('active');
        $user->setRole($role);
        $user->setGoogleId($googleId);
        $user->setPassword($password ?? '');

        return $user;
    }
}
