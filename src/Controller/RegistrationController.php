<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
{
    $user = new Users();
    $form = $this->createForm(RegistrationFormType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        // ✅ NOW form is mapped correctly
        $role = $user->getRole();

        if (!$role) {
            throw new \Exception('Role is required');
        }

        if (!in_array(strtolower($role->getName()), ['candidate', 'recruiter'])) {
            throw new \Exception('Invalid role');
        }

        // ✅ Hash password
        $plainPassword = $form->get('plainPassword')->getData();
        $user->setPassword(
            $userPasswordHasher->hashPassword($user, $plainPassword)
        );

        // ✅ Optional: default status
        $user->setStatus('active');

        $entityManager->persist($user);
        $entityManager->flush();

        return $this->redirectToRoute('app_login');
    }

    return $this->render('registration/register.html.twig', [
        'registrationForm' => $form,
    ]);
}
}
