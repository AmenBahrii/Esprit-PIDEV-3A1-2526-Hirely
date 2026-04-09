<?php

namespace App\Controller;

use App\Service\DatabaseService;
use App\Service\TemplateRenderer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/recruiter-dashboard')]
class RecruiterDashboardController
{
    #[Route('', name: 'app_dashboard')]
    public function index(): Response
    {
        $db = new DatabaseService();
        $renderer = new TemplateRenderer();
        
        $stats = $db->getDashboardStats();
        $upcoming = $db->getUpcomingInterviews();
        $notifications = $db->getNotifications();
        
        $html = $renderer->render('recruiter/dashboard.html.twig', [
            'total_interviews' => $stats['total_interviews'],
            'today_count' => $stats['today_count'],
            'pending_evaluations_count' => $stats['pending_evaluations_count'],
            'upcoming_interviews' => $upcoming,
            'unread_notifications' => $notifications,
        ]);
        
        return new Response($html);
    }
}
