<?php

namespace App\Service;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TemplateRenderer
{
    private Environment $twig;

    public function __construct()
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../templates');
        $this->twig = new Environment($loader);
        
        // Add custom functions
        $this->twig->addFunction(new TwigFunction('path', [$this, 'generatePath']));
        $this->twig->addFunction(new TwigFunction('asset', [$this, 'generateAsset']));
        $this->twig->addFunction(new TwigFunction('url', [$this, 'generateUrl']));
        $this->twig->addFunction(new TwigFunction('csrf_token', [$this, 'generateCsrfToken']));
    }

    public function generatePath(string $routeName, array $parameters = []): string
    {
        $routes = [
            'app_home' => '/',
            'app_login' => '/login',
            'app_logout' => '/logout',
            'app_dashboard' => '/recruiter-dashboard',
            'app_interviews' => '/interviews',
            'app_interview_new' => '/interviews/new',
            'app_interview_show' => '/interviews/' . ($parameters['id'] ?? ''),
            'app_interview_edit' => '/interviews/' . ($parameters['id'] ?? '') . '/edit',
            'app_interview_delete' => '/interviews/' . ($parameters['id'] ?? '') . '/delete',
            'app_interview_complete' => '/interviews/' . ($parameters['id'] ?? '') . '/complete',
            'app_evaluations' => '/evaluations',
            'app_evaluation_show' => '/evaluations/' . ($parameters['id'] ?? ''),
            'app_evaluation_edit' => '/evaluations/' . ($parameters['id'] ?? '') . '/edit',
            'app_evaluation_delete' => '/evaluations/' . ($parameters['id'] ?? '') . '/delete',
            'app_evaluation_form' => '/evaluations/interview/' . ($parameters['interviewId'] ?? '') . '/form',
            'app_evaluation_submit' => '/evaluations/submit',
            'app_applications' => '/applications',
            'app_application_new' => '/applications/new',
            'app_application_show' => '/applications/' . ($parameters['id'] ?? ''),
            'app_application_edit' => '/applications/' . ($parameters['id'] ?? '') . '/edit',
            'app_application_delete' => '/applications/' . ($parameters['id'] ?? '') . '/delete',
        ];
        
        return $routes[$routeName] ?? '#';
    }

    public function generateAsset(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function generateUrl(string $routeName, array $parameters = []): string
    {
        return $this->generatePath($routeName, $parameters);
    }

    public function generateCsrfToken(string $tokenId = 'token'): string
    {
        return bin2hex(random_bytes(16));
    }

    public function render(string $template, array $data = []): string
    {
        return $this->twig->render($template, $data);
    }
}
