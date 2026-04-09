<?php

namespace App\Controller;

use App\Service\DatabaseService;
use App\Service\TemplateRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/applications')]
class ApplicationController
{
    #[Route('', name: 'app_applications')]
    public function list(): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();
        
        $applications = $db->getApplications();
        
        $html = $renderer->render('application/list.html.twig', [
            'applications' => $applications,
        ]);
        
        return new Response($html);
    }

    #[Route('/new', name: 'app_application_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        if ($request->isMethod('POST')) {
            $data = [
                'candidate_name' => $request->request->get('candidate_name'),
                'job_id' => $request->request->get('job_id'),
                'status' => $request->request->get('status', 'applied'),
            ];

            try {
                $db->createApplication($data);
                return new Response('<script>window.location.href = "/applications"; alert("Application created successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error: ' . $e->getMessage(), 500);
            }
        }

        $html = $renderer->render('application/form.html.twig', [
            'application' => null,
        ]);

        return new Response($html);
    }

    #[Route('/{id}', name: 'app_application_show')]
    public function show(int $id): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $application = $db->getApplicationById($id);
        if (!$application) {
            return new Response('Application not found', 404);
        }

        // Get related interviews for this application
        $stmt = $db->getPDO()->prepare("
            SELECT i.id, i.schedule_date, i.format, i.status,
                   it.name as interview_type_name
            FROM interview i
            LEFT JOIN interview_type it ON i.interview_type_id = it.id
            WHERE i.application_id = :id
            ORDER BY i.schedule_date DESC
        ");
        $stmt->execute([':id' => $id]);
        $interviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $html = $renderer->render('application/show.html.twig', [
            'application' => $application,
            'interviews' => $interviews,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/edit', name: 'app_application_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $application = $db->getApplicationById($id);
        if (!$application) {
            return new Response('Application not found', 404);
        }

        if ($request->isMethod('POST')) {
            $data = [
                'candidate_name' => $request->request->get('candidate_name'),
                'job_id' => $request->request->get('job_id'),
                'status' => $request->request->get('status', 'applied'),
            ];

            try {
                $db->updateApplication($id, $data);
                return new Response('<script>window.location.href = "/applications"; alert("Application updated successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error: ' . $e->getMessage(), 500);
            }
        }

        $html = $renderer->render('application/form.html.twig', [
            'application' => $application,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/delete', name: 'app_application_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $db = new DatabaseService();

        $application = $db->getApplicationById($id);
        if (!$application) {
            return new Response('Application not found', 404);
        }

        try {
            $db->deleteApplication($id);
            return new Response('<script>window.location.href = "/applications"; alert("Application deleted successfully!");</script>');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }
}
