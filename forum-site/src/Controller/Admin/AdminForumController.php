<?php

namespace App\Controller\Admin;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Entity\User;
use App\Form\ForumCommentType;
use App\Form\ForumPostType;
use App\Repository\ForumCommentRepository;
use App\Repository\ForumPostRepository;
use App\Service\Forum\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/forum')]
class AdminForumController extends AbstractController
{
    #[Route('', name: 'admin_forum_dashboard')]
    public function dashboard(Request $request, ForumPostRepository $postRepository, ForumCommentRepository $commentRepository): Response
    {
        $status = (string) $request->query->get('status', 'ALL');
        $sort = (string) $request->query->get('sort', 'new');
        $search = $request->query->get('q');
        $pendingPosts = $postRepository->count(['status' => 'PENDING']);
        $pendingComments = $commentRepository->count(['status' => 'PENDING']);

        return $this->render('admin/dashboard.html.twig', [
            'posts' => $postRepository->findAdminFeed(is_string($search) ? $search : null, $status, $sort, $this->getUser()),
            'status' => $status,
            'sort' => $sort,
            'search' => is_string($search) ? $search : '',
            'pending_posts' => $pendingPosts,
            'pending_comments' => $pendingComments,
        ]);
    }

    #[Route('/post/{id}', name: 'admin_forum_post_show', requirements: ['id' => '\d+'])]
    public function showPost(ForumPost $post, Request $request, ForumCommentRepository $commentRepository): Response
    {
        return $this->render('admin/post_show.html.twig', [
            'post' => $post,
            'comments' => $commentRepository->findForPost($post, true, (string) $request->query->get('comments', 'new')),
        ]);
    }

    #[Route('/post/{id}/edit', name: 'admin_forum_post_edit', requirements: ['id' => '\d+'])]
    public function editPost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        $currentUser = $this->getCurrentUserEntity();
        $oldStatus = $post->getStatus();
        $form = $this->createForm(ForumPostType::class, $post, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post
                ->setEditedBy($currentUser)
                ->setEditedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();
            $notificationService->notifyPostStatusChanged($post, $currentUser, $oldStatus);
            $this->addFlash('success', 'Post moderation saved.');

            return $this->redirectToRoute('admin_forum_post_show', ['id' => $post->getId()]);
        }

        return $this->render('admin/post_form.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }

    #[Route('/post/{id}/delete', name: 'admin_forum_post_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePost(ForumPost $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post deleted.');
        }

        return $this->redirectToRoute('admin_forum_dashboard');
    }

    #[Route('/comment/{id}/edit', name: 'admin_forum_comment_edit', requirements: ['id' => '\d+'])]
    public function editComment(
        ForumComment $comment,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        $currentUser = $this->getCurrentUserEntity();
        $oldStatus = $comment->getStatus();
        $form = $this->createForm(ForumCommentType::class, $comment, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment
                ->setEditedBy($currentUser)
                ->setEditedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();
            $notificationService->notifyCommentStatusChanged($comment, $currentUser, $oldStatus);
            $this->addFlash('success', 'Comment moderation saved.');

            return $this->redirectToRoute('admin_forum_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        return $this->render('admin/comment_form.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'admin_forum_comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteComment(ForumComment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        $postId = $comment->getPost()?->getId();
        if ($this->isCsrfTokenValid('delete_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment deleted.');
        }

        return $this->redirectToRoute('admin_forum_post_show', ['id' => $postId]);
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
