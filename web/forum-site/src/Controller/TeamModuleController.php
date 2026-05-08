<?php

namespace App\Controller;

use App\Repository\TeamModuleRuntimeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamModuleController extends AbstractController
{
    #[Route('/joboffers', name: 'app_joboffer_index', methods: ['GET'])]
    public function jobOffers(TeamModuleRuntimeRepository $repository): Response
    {
        return $this->render('team_modules/runtime_index.html.twig', [
            'module_title' => 'Job Offer Module',
            'module_kicker' => 'Recruitment',
            'module_description' => 'Runtime page for the team Job Offer module. Data appears when the job offer schema is present.',
            'module_status' => 'Runtime shell active',
            'dashboard' => $repository->jobOfferDashboard(),
        ]);
    }

    #[Route('/applications', name: 'app_applications', methods: ['GET'])]
    public function applications(TeamModuleRuntimeRepository $repository): Response
    {
        return $this->render('team_modules/runtime_index.html.twig', [
            'module_title' => 'Application Module',
            'module_kicker' => 'Applications',
            'module_description' => 'Runtime page for the team Application module. It reads application rows when the schema is available.',
            'module_status' => 'Runtime shell active',
            'dashboard' => $repository->applicationDashboard(),
        ]);
    }

    #[Route('/evaluations', name: 'app_evaluations', methods: ['GET'])]
    public function evaluations(TeamModuleRuntimeRepository $repository): Response
    {
        return $this->render('team_modules/runtime_index.html.twig', [
            'module_title' => 'Evaluation / Assessment Module',
            'module_kicker' => 'Assessment',
            'module_description' => 'Runtime page for the team Evaluation and Assessment module. It reads interview evaluation rows when the schema is available.',
            'module_status' => 'Runtime shell active',
            'dashboard' => $repository->evaluationDashboard(),
        ]);
    }
}
