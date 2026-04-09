<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\RecruiterProfile;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function createRecruiter(string $email, string $password, string $name, ?string $department = null): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRole('ROLE_RECRUITER');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $recruiterProfile = new RecruiterProfile();
        $recruiterProfile->setUser($user);
        $recruiterProfile->setName($name);
        $recruiterProfile->setDepartment($department);

        $user->setRecruiterProfile($recruiterProfile);

        $this->entityManager->persist($user);
        $this->entityManager->persist($recruiterProfile);
        $this->entityManager->flush();

        return $user;
    }

    public function createInterviewee(string $email, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRole('ROLE_INTERVIEWEE');
        
        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
}
