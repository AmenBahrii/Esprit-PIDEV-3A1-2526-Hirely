<?php

namespace App\Controller;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Entity\User;
use App\Form\ForumCommentType;
use App\Form\ForumPostType;
use App\Repository\ForumCommentRepository;
use App\Repository\ForumInteractionRepository;
use App\Repository\ForumPostRepository;
use App\Service\Forum\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/')]
class ForumController extends AbstractController
{
    #[Route('', name: 'root_redirect')]
    public function root(): Response
    {
        if ($this->getUser() === null) {
            return $this->redirectToRoute('app_login');
        }

        return $this->redirectToRoute('app_home');
    }

    #[Route('forum', name: 'forum_index')]
    public function index(Request $request, ForumPostRepository $postRepository): Response
    {
        $currentUser = $this->getCurrentUserEntity();
        $sort = (string) $request->query->get('sort', 'new');
        $search = $request->query->get('q');
        $tag = $request->query->get('tag');
        $selectedTag = is_string($tag) && trim($tag) !== '' ? trim($tag) : 'ALL';
        $posts = $postRepository->findFeed(
            is_string($search) ? $search : null,
            $sort,
            $selectedTag,
            $currentUser
        );

        return $this->render('forum/index.html.twig', [
            'posts' => $posts,
            'sort' => $sort,
            'search' => is_string($search) ? $search : '',
            'selected_tag' => $selectedTag,
            'tags' => $postRepository->getFeedTags(),
        ]);
    }

    #[Route('forum/post/new', name: 'forum_post_new')]
    public function newPost(Request $request, EntityManagerInterface $entityManager, ForumPostRepository $postRepository): Response
    {
        $post = new ForumPost();
        $form = $this->createForm(ForumPostType::class, $post);
        $form->handleRequest($request);
        $currentUser = $this->getCurrentUserEntity();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if ($form->isSubmitted() && $form->isValid()) {
            if ($postRepository->hasRecentDuplicateByAuthor($currentUser, $post->getTitle(), $post->getContent())) {
                $form->addError(new FormError('Duplicate post detected. Please edit your recent post instead of posting the same content again.'));
            } else {
                $post
                    ->setAuthor($currentUser)
                    ->setStatus($isAdmin ? 'APPROVED' : 'PENDING')
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->persist($post);
                $entityManager->flush();

                $this->addFlash('success', $isAdmin ? 'Post published.' : 'Post submitted for moderation.');

                return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
            }
        }

        return $this->render('forum/post_form.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'page_title' => 'New Post',
            'submit_label' => 'Publish post',
        ], new Response('', $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('forum/post/{id}', name: 'forum_post_show', requirements: ['id' => '\d+'])]
    public function showPost(int $id, Request $request, ForumPostRepository $postRepository, ForumCommentRepository $commentRepository): Response
    {
        $currentUser = $this->getCurrentUserEntity();
        $post = $postRepository->findOneForDisplay($id, $currentUser);
        if ($post === null) {
            throw $this->createNotFoundException('Post not found.');
        }

        $includeHidden = $this->isGranted('ROLE_ADMIN');
        $commentSort = (string) $request->query->get('comments', 'new');
        $comments = $commentRepository->findForPost($post, $includeHidden, $commentSort, $currentUser);

        $comment = new ForumComment();
        $commentForm = $this->createForm(ForumCommentType::class, $comment, [
            'action' => $this->generateUrl('forum_comment_new', ['id' => $post->getId()]),
        ]);

        return $this->render('forum/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_form' => $commentForm->createView(),
            'comment_sort' => $commentSort,
        ]);
    }

    #[Route('forum/post/{id}/edit', name: 'forum_post_edit', requirements: ['id' => '\d+'])]
    public function editPost(ForumPost $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnerOrAdmin($post->getAuthor()?->getId());
        $currentUser = $this->getCurrentUserEntity();

        $form = $this->createForm(ForumPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isAdmin = $this->isGranted('ROLE_ADMIN');
            if (!$isAdmin) {
                $post->setStatus('PENDING');
            }

            $post
                ->setEditedBy($currentUser)
                ->setEditedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();
            $this->addFlash('success', $isAdmin ? 'Post updated.' : 'Post updated and sent for moderation review.');

            return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
        }

        return $this->render('forum/post_form.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'page_title' => 'Edit Post',
            'submit_label' => 'Save changes',
        ]);
    }

    #[Route('forum/post/{id}/delete', name: 'forum_post_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePost(ForumPost $post, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnerOrAdmin($post->getAuthor()?->getId());

        if ($this->isCsrfTokenValid('delete_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();
            $this->addFlash('success', 'Post deleted.');
        }

        return $this->redirectToRoute('forum_index');
    }

    #[Route('forum/post/{id}/like', name: 'forum_post_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleLike(
        int $id,
        Request $request,
        ForumPostRepository $postRepository,
        ForumInteractionRepository $interactionRepository,
        NotificationService $notificationService
    ): Response
    {
        $currentUser = $this->getCurrentUserEntity();

        if (!$this->isCsrfTokenValid('like_post_' . $id, (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('forum_post_show', ['id' => $id]);
        }

        $post = $postRepository->findOneForDisplay($id, $currentUser);
        if ($post !== null) {
            $liked = $interactionRepository->togglePostLike($id, $currentUser);
            if ($liked) {
                $notificationService->notifyPostLiked($post, $currentUser);
            }
            $this->addFlash('success', $liked ? 'Post liked.' : 'Like removed.');
        }

        return $this->redirectToRoute('forum_post_show', ['id' => $id]);
    }

    #[Route('forum/comment/{id}/like', name: 'forum_comment_like', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleCommentLike(
        ForumComment $comment,
        Request $request,
        ForumPostRepository $postRepository,
        ForumInteractionRepository $interactionRepository,
        NotificationService $notificationService
    ): Response {
        $currentUser = $this->getCurrentUserEntity();
        $commentId = $comment->getId();
        $postId = $comment->getPost()?->getId();

        if ($commentId === null || $postId === null) {
            return $this->redirectToRoute('forum_index');
        }

        if (!$this->isCsrfTokenValid('like_comment_' . $commentId, (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('forum_post_show', ['id' => $postId]);
        }

        $post = $postRepository->findOneForDisplay($postId, $currentUser);
        if ($post === null) {
            throw $this->createNotFoundException('Post not found.');
        }

        $liked = $interactionRepository->toggleCommentLike($commentId, $currentUser);
        if ($liked) {
            $notificationService->notifyCommentLiked($comment, $currentUser);
        }

        $this->addFlash('success', $liked ? 'Comment liked.' : 'Comment like removed.');

        return $this->redirectToRoute('forum_post_show', ['id' => $postId]);
    }

    #[Route('forum/post/{id}/comment/new', name: 'forum_comment_new', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function newComment(
        int $id,
        Request $request,
        ForumPostRepository $postRepository,
        ForumCommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        $post = $postRepository->findOneForDisplay($id, $this->getUser());
        if ($post === null) {
            throw $this->createNotFoundException('Post not found.');
        }

        if ($post->isLocked() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Comments are locked for this post.');

            return $this->redirectToRoute('forum_post_show', ['id' => $id]);
        }

        $comment = new ForumComment();
        $form = $this->createForm(ForumCommentType::class, $comment);
        $form->handleRequest($request);
        $currentUser = $this->getCurrentUserEntity();

        if ($form->isSubmitted() && $form->isValid()) {
            $isAdmin = $this->isGranted('ROLE_ADMIN');
            if ($commentRepository->hasRecentDuplicateForPostByAuthor($post, $currentUser, $comment->getContent())) {
                $form->addError(new FormError('Duplicate comment detected. Please avoid posting the same comment repeatedly.'));
            } else {
                $comment
                    ->setPost($post)
                    ->setAuthor($currentUser)
                    ->setStatus($isAdmin ? 'APPROVED' : 'PENDING')
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->persist($comment);
                $entityManager->flush();
                $notificationService->notifyPostCommented($post, $comment, $currentUser);
                $this->addFlash('success', $isAdmin ? 'Comment added.' : 'Comment submitted for moderation.');

                return $this->redirectToRoute('forum_post_show', ['id' => $id]);
            }
        }

        $comments = $commentRepository->findForPost($post, $this->isGranted('ROLE_ADMIN'), 'new', $currentUser);

        return $this->render('forum/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_form' => $form->createView(),
            'comment_sort' => 'new',
        ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[Route('forum/comment/{id}/edit', name: 'forum_comment_edit', requirements: ['id' => '\d+'])]
    public function editComment(ForumComment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnerOrAdmin($comment->getAuthor()?->getId());
        $currentUser = $this->getCurrentUserEntity();

        if ($comment->getPost()?->isLocked() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Comments are locked for this post.');

            return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        $form = $this->createForm(ForumCommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isAdmin = $this->isGranted('ROLE_ADMIN');
            if (!$isAdmin) {
                $comment->setStatus('PENDING');
            }

            $comment
                ->setEditedBy($currentUser)
                ->setEditedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();
            $this->addFlash('success', $isAdmin ? 'Comment updated.' : 'Comment updated and sent for moderation review.');

            return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        return $this->render('forum/comment_form.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }

    #[Route('forum/comment/{id}/delete', name: 'forum_comment_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteComment(ForumComment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessOwnerOrAdmin($comment->getAuthor()?->getId());
        $postId = $comment->getPost()?->getId();

        if ($this->isCsrfTokenValid('delete_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment deleted.');
        }

        return $this->redirectToRoute('forum_post_show', ['id' => $postId]);
    }

    private function denyUnlessOwnerOrAdmin(?int $ownerId): void
    {
        $currentUser = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (!$currentUser instanceof User || $ownerId === null || $currentUser->getId() !== $ownerId) {
            throw $this->createAccessDeniedException();
        }
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
