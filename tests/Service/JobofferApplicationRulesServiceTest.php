<?php

namespace App\Tests\Service;

use App\Entity\Application;
use App\Entity\Joboffer;
use App\Entity\Role;
use App\Entity\Users;
use App\Service\JobofferApplicationRulesService;
use PHPUnit\Framework\TestCase;

class JobofferApplicationRulesServiceTest extends TestCase
{
    private JobofferApplicationRulesService $service;

    protected function setUp(): void
    {
        $this->service = new JobofferApplicationRulesService();
    }

    public function testRecruiterOwnedJobOfferCanBePublished(): void
    {
        $joboffer = $this->buildJoboffer('CDI', 2, 'Open', $this->buildRecruiter());

        self::assertTrue($this->service->assertJobOfferCanBePublished($joboffer));
    }

    public function testNonRecruiterCannotOwnJobOffer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only recruiters can own job offers.');

        $joboffer = $this->buildJoboffer('CDI', 2, 'Open', $this->buildCandidate());
        $this->service->assertJobOfferCanBePublished($joboffer);
    }

    public function testInternshipCannotRequireMoreThanTwoYears(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Internships cannot require more than 2 years of experience.');

        $joboffer = $this->buildJoboffer('Internship', 4, 'Open', $this->buildRecruiter());
        $this->service->assertJobOfferCanBePublished($joboffer);
    }

    public function testClosedOfferCannotReceiveApplication(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('You can only apply to open job offers.');

        $application = $this->buildApplication($this->buildJoboffer('CDI', 2, 'Closed', $this->buildRecruiter()));
        $this->service->assertApplicationCanBeSubmitted($application);
    }

    public function testApplicationEmailMustMatchCandidate(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Application email must match the candidate account email.');

        $application = $this->buildApplication($this->buildJoboffer('CDI', 2, 'Open', $this->buildRecruiter()));
        $application->setEmail('other@example.com');

        $this->service->assertApplicationCanBeSubmitted($application);
    }

    public function testCandidateExperienceMustMeetRequirement(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Candidate experience must meet the job offer requirement.');

        $application = $this->buildApplication($this->buildJoboffer('CDI', 4, 'Open', $this->buildRecruiter()));
        $application->setExperienceYears(1);

        $this->service->assertApplicationCanBeSubmitted($application);
    }

    public function testPendingCandidateApplicationCanBeDeleted(): void
    {
        $candidate = $this->buildCandidate();
        $application = $this->buildApplication($this->buildJoboffer('CDI', 2, 'Open', $this->buildRecruiter()), $candidate);

        self::assertTrue($this->service->canCandidateDelete($application, $candidate));
    }

    private function buildRole(string $name): Role
    {
        $role = new Role();
        $role->setName($name);
        $role->setDescription('Role for tests');
        $role->setStatus('active');
        $role->setDefaultDashboard($name . '_dashboard');

        return $role;
    }

    private function buildRecruiter(): Users
    {
        $recruiter = new Users();
        $recruiter->setId(50);
        $recruiter->setFirstName('Recruiter');
        $recruiter->setLastName('Owner');
        $recruiter->setEmail('recruiter@example.com');
        $recruiter->setStatus('active');
        $recruiter->setRole($this->buildRole('recruiter'));
        $recruiter->setPassword('secret123');

        return $recruiter;
    }

    private function buildCandidate(): Users
    {
        $candidate = new Users();
        $candidate->setId(75);
        $candidate->setFirstName('Candidate');
        $candidate->setLastName('Applicant');
        $candidate->setEmail('candidate@example.com');
        $candidate->setStatus('active');
        $candidate->setRole($this->buildRole('candidate'));
        $candidate->setPassword('secret123');

        return $candidate;
    }

    private function buildJoboffer(string $contractType, int $experienceRequired, string $status, Users $owner): Joboffer
    {
        $joboffer = new Joboffer();
        $joboffer->setTitle('Symfony Developer');
        $joboffer->setDescription('A detailed Symfony job offer used for workshop unit tests.');
        $joboffer->setContractType($contractType);
        $joboffer->setSalary(2500);
        $joboffer->setLocation('Tunis');
        $joboffer->setExperienceRequired($experienceRequired);
        $joboffer->setPublicationDate(new \DateTimeImmutable('2026-05-01'));
        $joboffer->setStatus($status);
        $joboffer->setUser($owner);

        return $joboffer;
    }

    private function buildApplication(?Joboffer $joboffer = null, ?Users $candidate = null): Application
    {
        $candidate ??= $this->buildCandidate();
        $joboffer ??= $this->buildJoboffer('CDI', 2, 'Open', $this->buildRecruiter());

        $application = new Application();
        $application->setUser($candidate);
        $application->setJobOffer($joboffer);
        $application->setApplicationDate(new \DateTimeImmutable('2026-05-02'));
        $application->setCoverLetter('I would love to join this team and contribute to the Symfony platform.');
        $application->setCurrentStatus('pending');
        $application->setResumePath('resume.pdf');
        $application->setLastUpdateDate(new \DateTimeImmutable('2026-05-02 10:00:00'));
        $application->setExpectedSalary(2200);
        $application->setAvailabilityDate(new \DateTimeImmutable('2026-05-10'));
        $application->setPhone('+216 55 555 555');
        $application->setEmail($candidate->getEmail());
        $application->setExperienceYears(3);
        $application->setPortfolioUrl('https://portfolio.example.com');

        return $application;
    }
}
