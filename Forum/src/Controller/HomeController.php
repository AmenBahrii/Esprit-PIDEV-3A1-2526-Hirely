<?php

namespace App\Controller;

use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    #[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
    public function index(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException();
        }

        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);
        $roleName = strtolower($user->getRole()?->getName() ?? 'member');

        $modules = [
            [
                'title' => 'Forum',
                'subtitle' => 'Community discussions, comments, likes, moderation, and notifications in the shared Hirely shell.',
                'route' => 'forum_index',
                'cta' => 'Open Forum',
                'label' => 'Community',
            ],
            [
                'title' => 'Job Offers',
                'subtitle' => 'Browse or manage opportunities with the existing Hirely recruitment workflows.',
                'route' => 'app_joboffer_index',
                'cta' => $roleName === 'recruiter' ? 'Manage Offers' : 'Browse Offers',
                'label' => 'Hiring',
            ],
            [
                'title' => 'Applications',
                'subtitle' => 'Review current applications without changing the established business flow.',
                'route' => 'app_application_index',
                'cta' => 'Open Applications',
                'label' => 'Pipeline',
            ],
        ];

        if ($isAdmin) {
            $modules[] = [
                'title' => 'Forum Moderation',
                'subtitle' => 'Review posts, comments, moderation notes, feedback, and AI-assisted actions.',
                'route' => 'admin_forum_dashboard',
                'cta' => 'Open Moderation',
                'label' => 'Admin',
            ];
            $modules[] = [
                'title' => 'User Management',
                'subtitle' => 'Keep the existing Hirely user and role administration screens accessible from the same hub.',
                'route' => 'app_users_index',
                'cta' => 'Open Users',
                'label' => 'Admin',
            ];
        }

        return $this->render('home/index.html.twig', [
            'modules' => $modules,
            'is_admin_home' => $isAdmin,
        ]);
    }
}
