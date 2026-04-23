<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\GoogleOAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        GoogleOAuthService $googleOAuthService,
    ): Response {
        $user = new User();
        $user->setStatus('active');

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        $plainPassword = $form->get('plainPassword')->getData();
        if (is_string($plainPassword) && $plainPassword !== '') {
            // Keep the entity valid during form validation; hash after success.
            $user->setPassword($plainPassword);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $role = $user->getRole();
            if (!$role || !in_array(strtolower($role->getName()), ['candidate', 'recruiter'], true)) {
                $this->addFlash('error', 'Please choose a valid account type.');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'google_auth_enabled' => $googleOAuthService->isConfigured(),
                ]);
            }

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Account created successfully. You can log in now.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'google_auth_enabled' => $googleOAuthService->isConfigured(),
        ]);
    }
}
