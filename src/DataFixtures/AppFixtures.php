<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\RecruiterProfile;
use App\Entity\InterviewType;
use App\Entity\EvaluationCriteria;
use App\Entity\Application;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        // Create Interview Types
        $phoneScreening = new InterviewType();
        $phoneScreening->setName('Phone Screening');
        $phoneScreening->setDescription('Initial phone screening interview');
        $manager->persist($phoneScreening);

        $technicalInterview = new InterviewType();
        $technicalInterview->setName('Technical Interview');
        $technicalInterview->setDescription('Technical skills assessment');
        $manager->persist($technicalInterview);

        $behavioralInterview = new InterviewType();
        $behavioralInterview->setName('Behavioral Interview');
        $behavioralInterview->setDescription('Behavioral and cultural fit assessment');
        $manager->persist($behavioralInterview);

        $finalRound = new InterviewType();
        $finalRound->setName('Final Round');
        $finalRound->setDescription('Final presentation and discussion');
        $manager->persist($finalRound);

        // Create Evaluation Criteria
        $criteria = [
            ['name' => 'Technical Skills', 'description' => 'Proficiency in required technologies and methodologies'],
            ['name' => 'Communication', 'description' => 'Ability to articulate ideas clearly'],
            ['name' => 'Problem Solving', 'description' => 'Approach to solving complex problems'],
            ['name' => 'Teamwork', 'description' => 'Collaboration and team fit'],
            ['name' => 'Experience', 'description' => 'Relevant work experience and background'],
            ['name' => 'Cultural Fit', 'description' => 'Alignment with company values and culture'],
        ];

        foreach ($criteria as $criterionData) {
            $criterion = new EvaluationCriteria();
            $criterion->setName($criterionData['name']);
            $criterion->setDescription($criterionData['description']);
            $criterion->setWeight(1.0);
            $manager->persist($criterion);
        }

        // Create Recruiter User
        $recruiterUser = new User();
        $recruiterUser->setEmail('recruiter@example.com');
        $recruiterUser->setRole('ROLE_RECRUITER');
        
        $recruiterPassword = $this->passwordHasher->hashPassword($recruiterUser, 'password');
        $recruiterUser->setPassword($recruiterPassword);

        $recruiterProfile = new RecruiterProfile();
        $recruiterProfile->setUser($recruiterUser);
        $recruiterProfile->setName('John Recruiter');
        $recruiterProfile->setDepartment('Human Resources');

        $recruiterUser->setRecruiterProfile($recruiterProfile);
        $manager->persist($recruiterUser);
        $manager->persist($recruiterProfile);

        // Create Interviewee User
        $candidateUser = new User();
        $candidateUser->setEmail('candidate@example.com');
        $candidateUser->setRole('ROLE_INTERVIEWEE');
        
        $candidatePassword = $this->passwordHasher->hashPassword($candidateUser, 'password');
        $candidateUser->setPassword($candidatePassword);
        $manager->persist($candidateUser);

        // Create Sample Applications
        $applicant1 = new Application();
        $applicant1->setCandidateName('Alice Johnson');
        $applicant1->setJobId('JOB-001');
        $applicant1->setStatus('applied');
        $manager->persist($applicant1);

        $applicant2 = new Application();
        $applicant2->setCandidateName('Bob Smith');
        $applicant2->setJobId('JOB-001');
        $applicant2->setStatus('shortlisted');
        $manager->persist($applicant2);

        $applicant3 = new Application();
        $applicant3->setCandidateName('Carol White');
        $applicant3->setJobId('JOB-002');
        $applicant3->setStatus('applied');
        $manager->persist($applicant3);

        $manager->flush();
    }
}
