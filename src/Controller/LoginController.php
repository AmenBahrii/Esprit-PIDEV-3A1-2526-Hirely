<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LoginController
{
    #[Route('/login', name: 'app_login')]
    public function login(): Response
    {
        return new Response(file_get_contents(__DIR__ . '/../../templates/security/login.html.twig'));
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        // will be handled by Symfony
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return new Response(
            '<h1>Welcome to Hirely Interview Evaluation System</h1>' .
            '<p><a href="/login">Go to Login</a></p>' .
            '<p><a href="/recruiter-dashboard">Go to Dashboard</a></p>'
        );
    }
}
