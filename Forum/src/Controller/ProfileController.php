<?php

namespace App\Controller;

use App\Entity\Users;
use App\Repository\ForumCommentRepository;
use App\Repository\ForumPostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profile')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
class ProfileController extends AbstractController
{
    #[Route('', name: 'profile_me')]
    public function me(): Response
    {
        $viewer = $this->getCurrentUserEntity();

        return $this->redirectToRoute('profile_show', ['id' => $viewer->getId()]);
    }

    #[Route('/{id}', name: 'profile_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        EntityManagerInterface $entityManager,
        ForumPostRepository $postRepository,
        ForumCommentRepository $commentRepository,
    ): Response {
        $viewer = $this->getCurrentUserEntity();
        $profileUser = $entityManager->getRepository(Users::class)->find($id);
        if (!$profileUser instanceof Users) {
            throw $this->createNotFoundException('Profile not found.');
        }

        $canSeeHidden = $this->isGranted('ROLE_ADMIN') || $viewer->getId() === $profileUser->getId();
        $posts = $postRepository->findByAuthorForProfile($profileUser, $canSeeHidden, 'new', $viewer);
        $comments = $commentRepository->findRecentByAuthorForProfile($profileUser, $canSeeHidden, 12);

        return $this->render('profile/show.html.twig', [
            'profile_user' => $profileUser,
            'posts' => $posts,
            'comments' => $comments,
            'is_owner' => $viewer->getId() === $profileUser->getId(),
            'can_see_hidden' => $canSeeHidden,
            'profile_is_admin' => in_array('ROLE_ADMIN', $profileUser->getRoles(), true),
            'post_count' => count($posts),
            'comment_count' => count($comments),
        ]);
    }

    private function getCurrentUserEntity(): Users
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}



