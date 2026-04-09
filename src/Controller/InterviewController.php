<?php

namespace App\Controller;

use App\Service\DatabaseService;
use App\Service\TemplateRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/interviews')]
class InterviewController
{
    #[Route('', name: 'app_interviews')]
    public function list(): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();
        
        $interviews = $db->getInterviews();
        
        $html = $renderer->render('interview/list.html.twig', [
            'interviews' => $interviews,
        ]);
        
        return new Response($html);
    }

    #[Route('/new', name: 'app_interview_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        if ($request->isMethod('POST')) {
            $data = [
                'application_id' => $request->request->get('application_id'),
                'interview_type_id' => $request->request->get('interview_type_id'),
                'schedule_date' => $request->request->get('schedule_date'),
                'format' => $request->request->get('format'),
                'location' => $request->request->get('location'),
                'meeting_link' => $request->request->get('meeting_link'),
                'status' => $request->request->get('status') ?? 'scheduled',
                'user_id' => 1,
            ];

            try {
                $db->createInterview($data);
                return new Response('<script>window.location.href = "/interviews"; alert("Interview created successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error creating interview: ' . $e->getMessage(), 500);
            }
        }

        $applications = $db->getApplications();
        $interview_types = $db->getInterviewTypes();

        $html = $renderer->render('interview/form.html.twig', [
            'action_title' => 'Create',
            'button_text' => 'Create Interview',
            'applications' => $applications,
            'interview_types' => $interview_types,
            'interview' => [],
        ]);

        return new Response($html);
    }

    #[Route('/{id}', name: 'app_interview_show')]
    public function show(int $id): Response
    {
        $renderer = new TemplateRenderer();
        return new Response($renderer->render('interview/show.html.twig', [
            'interview' => ['id' => $id],
        ]));
    }

    #[Route('/{id}/edit', name: 'app_interview_edit', methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();

        $interview = $db->getInterviewById($id);
        if (!$interview) {
            return new Response('Interview not found', 404);
        }

        if ($request->isMethod('POST')) {
            $data = [
                'application_id' => $request->request->get('application_id'),
                'interview_type_id' => $request->request->get('interview_type_id'),
                'schedule_date' => $request->request->get('schedule_date'),
                'format' => $request->request->get('format'),
                'location' => $request->request->get('location'),
                'meeting_link' => $request->request->get('meeting_link'),
                'status' => $request->request->get('status'),
            ];

            try {
                $db->updateInterview($id, $data);
                return new Response('<script>window.location.href = "/interviews"; alert("Interview updated successfully!");</script>');
            } catch (\Exception $e) {
                return new Response('Error updating interview: ' . $e->getMessage(), 500);
            }
        }

        $applications = $db->getApplications();
        $interview_types = $db->getInterviewTypes();

        $html = $renderer->render('interview/form.html.twig', [
            'action_title' => 'Edit',
            'button_text' => 'Update Interview',
            'applications' => $applications,
            'interview_types' => $interview_types,
            'interview' => $interview,
        ]);

        return new Response($html);
    }

    #[Route('/{id}/delete', name: 'app_interview_delete', methods: ['POST'])]
    public function delete(int $id): Response
    {
        $db = new DatabaseService();

        try {
            $db->deleteInterview($id);
            return new Response('<script>window.location.href = "/interviews"; alert("Interview deleted successfully!");</script>');
        } catch (\Exception $e) {
            return new Response('Error deleting interview: ' . $e->getMessage(), 500);
        }
    }

    #[Route('/{id}/complete', name: 'app_interview_complete', methods: ['POST'])]
    public function complete(int $id): Response
    {
        $db = new DatabaseService();
        try {
            $interview = $db->getInterviewById($id);
            $db->updateInterview($id, array_merge($interview, ['status' => 'completed']));
            return new Response('<script>window.location.href = "/interviews"; alert("Interview completed!");</script>');
        } catch (\Exception $e) {
            return new Response('Error: ' . $e->getMessage(), 500);
        }
    }
}
