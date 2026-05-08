<?php

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\Joboffer;
use App\Entity\Role;
use App\Entity\Users;
use App\Service\ApplicationManager;
use PHPUnit\Framework\TestCase;

class ApplicationManagerTest extends TestCase
{
    public function testValidApplication(): void
    {
        $application = $this->createValidApplication();

        $manager = new ApplicationManager();

        $this->assertTrue($manager->validate($application));
    }

    public function testApplicationWithMismatchedEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Application email must match the candidate email');

        $application = $this->createValidApplication();
        $application->setEmail('other@example.com');

        $manager = new ApplicationManager();
        $manager->validate($application);
    }

    public function testApplicationForClosedJobOffer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Applications are only allowed for open job offers');

        $application = $this->createValidApplication();
        $application->getJobOffer()->setStatus('Closed');

        $manager = new ApplicationManager();
        $manager->validate($application);
    }

    public function testApplicationWithInsufficientExperience(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Candidate experience does not meet the job offer requirement');

        $application = $this->createValidApplication();
        $application->setExperienceYears(1);
        $application->getJobOffer()->setExperienceRequired(3);

        $manager = new ApplicationManager();
        $manager->validate($application);
    }

    public function testApplicationWithNegativeExpectedSalary(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected salary cannot be negative');

        $application = $this->createValidApplication();
        $application->setExpectedSalary(-100);

        $manager = new ApplicationManager();
        $manager->validate($application);
    }

    public function testApplicationWithInvalidPortfolioUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Portfolio URL is invalid');

        $application = $this->createValidApplication();
        $application->setPortfolioUrl('not-a-url');

        $manager = new ApplicationManager();
        $manager->validate($application);
    }

    public function testApplicationWithAvailabilityDateBeforeApplicationDate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Availability date cannot be before the application date');

        $application = $this->createValidApplication();
        $application->setAvailabilityDate(new \DateTimeImmutable('2026-01-01'));
        $application->setApplicationDate(new \DateTimeImmutable('2026-02-01'));

        $manager = new ApplicationManager();
        $manager->validate($application);
    }

    private function createValidApplication(): Application
    {
        $candidate = new Users();
        $candidate->setFirstName('Mariem');
        $candidate->setLastName('Trabelsi');
        $candidate->setEmail('mariem@example.com');
        $candidate->setPassword('secret123');
        $candidate->setStatus('active');
        $candidate->setRole($this->createRole('candidate', 'active'));

        $recruiter = new Users();
        $recruiter->setFirstName('Sami');
        $recruiter->setLastName('Ben Ali');
        $recruiter->setEmail('recruiter@example.com');
        $recruiter->setPassword('secret123');
        $recruiter->setStatus('active');
        $recruiter->setRole($this->createRole('recruiter', 'active'));

        $jobOffer = new Joboffer();
        $jobOffer->setTitle('Symfony Developer');
        $jobOffer->setDescription('We are looking for a Symfony developer with backend experience.');
        $jobOffer->setContractType('CDI');
        $jobOffer->setSalary(2500);
        $jobOffer->setLocation('Tunis');
        $jobOffer->setExperienceRequired(2);
        $jobOffer->setPublicationDate(new \DateTimeImmutable('2026-01-01'));
        $jobOffer->setStatus('Open');
        $jobOffer->setUser($recruiter);

        $application = new Application();
        $application->setUser($candidate);
        $application->setJobOffer($jobOffer);
        $application->setApplicationDate(new \DateTimeImmutable('2026-02-01'));
        $application->setAvailabilityDate(new \DateTimeImmutable('2026-02-15'));
        $application->setCoverLetter('I am very interested in this Symfony position.');
        $application->setCurrentStatus('pending');
        $application->setLastUpdateDate(new \DateTimeImmutable('2026-02-01 10:00:00'));
        $application->setExpectedSalary(1800);
        $application->setPhone('+216 55 123 456');
        $application->setEmail('mariem@example.com');
        $application->setExperienceYears(3);
        $application->setPortfolioUrl('https://portfolio.example.com');

        return $application;
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
