<?php

namespace App\Controller;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Entity\Users;
use App\Form\ForumCommentType;
use App\Form\ForumPostType;
use App\Repository\ForumCommentRepository;
use App\Repository\ForumInteractionRepository;
use App\Repository\ForumPostRepository;
use App\Service\Forum\AI\ModerationEngine;
use App\Service\Forum\AI\ModerationNoteFormatter;
use App\Service\Forum\GeminiBotService;
use App\Service\Forum\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Omines\AntiSpamBundle\AntiSpam;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/')]
#[IsGranted('IS_AUTHENTICATED_REMEMBERED')]
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
    public function index(Request $request, ForumPostRepository $postRepository, PaginatorInterface $paginator): Response
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
        $posts = $paginator->paginate(
            $posts,
            $request->query->getInt('page', 1),
            9,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'page']
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
    public function newPost(
        Request $request,
        EntityManagerInterface $entityManager,
        ForumPostRepository $postRepository,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        $post = new ForumPost();
        $form = $this->createForm(ForumPostType::class, $post, [
            'antispam_profile' => 'forum_post_submission',
        ]);
        $form->handleRequest($request);
        $currentUser = $this->getCurrentUserEntity();

        if ($form->isSubmitted() && $form->isValid()) {
            if ($postRepository->hasRecentDuplicateByAuthor($currentUser, $post->getTitle(), $post->getContent())) {
                $form->addError(new FormError('Duplicate post detected. Please edit your recent post instead of posting the same content again.'));
            } else {
                $report = $moderationEngine->analyzePost(
                    $this->buildPostModerationText($post),
                    sprintf('post:new:%d:%s', $currentUser->getId() ?? 0, substr(sha1($post->getTitle() . $post->getContent()), 0, 12))
                );
                $normalizedTag = $this->normalizeTag($post->getTag());
                $predictedTag = $this->normalizeTag($report->getPredictedCategory());

                $post
                    ->setAuthor($currentUser)
                    ->setTag($normalizedTag ?? $predictedTag ?? '#General')
                    ->setStatus($report->getDecision())
                    ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->persist($post);
                $entityManager->flush();

                $this->addFlash('success', $this->messageForPostStatus($report, false));

                return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
            }
        }

        if ($form->isSubmitted() && !$form->isValid() && $this->isAntiSpamRejected($form)) {
            $this->addFlash('error', 'Hirely anti-spam blocked this post submission. Remove suspicious links or markup and try again.');
        }

        return $this->render('forum/post_form.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'page_title' => 'New Post',
            'submit_label' => 'Publish post',
        ], new Response('', $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('forum/post/{id}', name: 'forum_post_show', requirements: ['id' => '\d+'])]
    public function showPost(int $id, Request $request, ForumPostRepository $postRepository, ForumCommentRepository $commentRepository, PaginatorInterface $paginator): Response
    {
        $currentUser = $this->getCurrentUserEntity();
        $post = $postRepository->findOneForDisplay($id, $currentUser);
        if ($post === null) {
            throw $this->createNotFoundException('Post not found.');
        }

        $includeHidden = $this->isGranted('ROLE_ADMIN');
        $commentSort = (string) $request->query->get('comments', 'new');
        $comments = $commentRepository->findForPost($post, $includeHidden, $commentSort, $currentUser);
        $comments = $paginator->paginate(
            $comments,
            $request->query->getInt('commentPage', 1),
            8,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'commentPage']
        );

        $comment = new ForumComment();
        $commentForm = $this->createForm(ForumCommentType::class, $comment, [
            'action' => $this->generateUrl('forum_comment_new', [
                'id' => $post->getId(),
                'comments' => $commentSort,
                'commentPage' => $comments->getCurrentPageNumber(),
            ]),
            'antispam_profile' => 'forum_comment_submission',
        ]);

        return $this->render('forum/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_form' => $commentForm->createView(),
            'comment_sort' => $commentSort,
        ]);
    }

    #[Route('forum/post/{id}/edit', name: 'forum_post_edit', requirements: ['id' => '\d+'])]
    public function editPost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        $this->denyUnlessOwnerOrAdmin($post->getAuthor()?->getId());
        $currentUser = $this->getCurrentUserEntity();

        $form = $this->createForm(ForumPostType::class, $post, [
            'antispam_profile' => 'forum_post_submission',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report = $moderationEngine->analyzePost(
                $this->buildPostModerationText($post),
                sprintf('post:%d', $post->getId() ?? 0)
            );
            $normalizedTag = $this->normalizeTag($post->getTag());
            $predictedTag = $this->normalizeTag($report->getPredictedCategory());

            $post
                ->setTag($normalizedTag ?? $predictedTag ?? '#General')
                ->setStatus($report->getDecision())
                ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
                ->setEditedBy($currentUser)
                ->setEditedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();
            $this->addFlash('success', $this->messageForPostStatus($report, true));

            return $this->redirectToRoute('forum_post_show', ['id' => $post->getId()]);
        }

        return $this->render('forum/post_form.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
            'page_title' => 'Edit Post',
            'submit_label' => 'Save changes',
        ]);
    }

    #[Route('forum/post/{id}/delete', name: 'forum_post_delete_confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirmDeletePost(ForumPost $post): Response
    {
        $this->denyUnlessOwnerOrAdmin($post->getAuthor()?->getId());

        return $this->render('forum/confirm_delete.html.twig', [
            'subject' => 'post',
            'title' => 'Delete post',
            'message' => 'This will permanently remove the post, its comments, and related likes from the forum.',
            'cancel_route' => 'forum_post_show',
            'cancel_route_params' => ['id' => $post->getId()],
            'delete_route' => 'forum_post_delete',
            'delete_route_params' => ['id' => $post->getId()],
            'csrf_token_id' => 'delete_post_' . $post->getId(),
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
        NotificationService $notificationService,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter,
        GeminiBotService $geminiBotService,
        PaginatorInterface $paginator
    ): Response
    {
        $post = $postRepository->findOneForDisplay($id, $this->getCurrentUserEntity());
        if ($post === null) {
            throw $this->createNotFoundException('Post not found.');
        }

        if ($post->isLocked() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Comments are locked for this post.');

            return $this->redirectToRoute('forum_post_show', ['id' => $id]);
        }

        $comment = new ForumComment();
        $commentSort = (string) $request->query->get('comments', 'new');
        $commentPage = $request->query->getInt('commentPage', 1);
        $form = $this->createForm(ForumCommentType::class, $comment, [
            'antispam_profile' => 'forum_comment_submission',
            'action' => $this->generateUrl('forum_comment_new', [
                'id' => $id,
                'comments' => $commentSort,
                'commentPage' => $commentPage,
            ]),
        ]);
        $form->handleRequest($request);
        $currentUser = $this->getCurrentUserEntity();

        if ($form->isSubmitted() && $form->isValid()) {
            if ($commentRepository->hasRecentDuplicateForPostByAuthor($post, $currentUser, $comment->getContent())) {
                $form->addError(new FormError('Duplicate comment detected. Please avoid posting the same comment repeatedly.'));
            } else {
                $report = $moderationEngine->analyzeComment(
                    $comment->getContent(),
                    sprintf('comment:new:%d:%d:%s', $post->getId() ?? 0, $currentUser->getId() ?? 0, substr(sha1($comment->getContent()), 0, 10))
                );

                $comment
                    ->setPost($post)
                    ->setAuthor($currentUser)
                    ->setStatus($report->getDecision())
                    ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setUpdatedAt(new \DateTimeImmutable());

                $entityManager->persist($comment);
                $entityManager->flush();
                $notificationService->notifyPostCommented($post, $comment, $currentUser);

                if ($geminiBotService->hasTrigger($comment->getContent())) {
                    $geminiBotService->createReplyForComment($post, $comment->getContent());
                }

                $this->addFlash('success', $this->messageForCommentStatus($report, false));

                return $this->redirectToRoute('forum_post_show', ['id' => $id]);
            }
        }

        $comments = $commentRepository->findForPost($post, $this->isGranted('ROLE_ADMIN'), $commentSort, $currentUser);
        $comments = $paginator->paginate(
            $comments,
            $commentPage,
            8,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'commentPage']
        );

        if ($form->isSubmitted() && !$form->isValid() && $this->isAntiSpamRejected($form)) {
            $this->addFlash('error', 'Hirely anti-spam blocked this comment submission. Remove suspicious links or markup and try again.');
        }

        return $this->render('forum/show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_form' => $form->createView(),
            'comment_sort' => $commentSort,
        ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
    }

    #[Route('forum/comment/{id}/edit', name: 'forum_comment_edit', requirements: ['id' => '\d+'])]
    public function editComment(
        ForumComment $comment,
        Request $request,
        EntityManagerInterface $entityManager,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        $this->denyUnlessOwnerOrAdmin($comment->getAuthor()?->getId());
        $currentUser = $this->getCurrentUserEntity();

        if ($comment->getPost()?->isLocked() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Comments are locked for this post.');

            return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        $form = $this->createForm(ForumCommentType::class, $comment, [
            'antispam_profile' => 'forum_comment_submission',
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $report = $moderationEngine->analyzeComment(
                $comment->getContent(),
                sprintf('comment:%d', $comment->getId() ?? 0)
            );

            $comment
                ->setStatus($report->getDecision())
                ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
                ->setEditedBy($currentUser)
                ->setEditedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->flush();
            $this->addFlash('success', $this->messageForCommentStatus($report, true));

            return $this->redirectToRoute('forum_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        return $this->render('forum/comment_form.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }

    #[Route('forum/comment/{id}/delete', name: 'forum_comment_delete_confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirmDeleteComment(ForumComment $comment): Response
    {
        $this->denyUnlessOwnerOrAdmin($comment->getAuthor()?->getId());

        return $this->render('forum/confirm_delete.html.twig', [
            'subject' => 'comment',
            'title' => 'Delete comment',
            'message' => 'This will permanently remove the comment and its likes from the discussion.',
            'cancel_route' => 'forum_post_show',
            'cancel_route_params' => ['id' => $comment->getPost()?->getId()],
            'delete_route' => 'forum_comment_delete',
            'delete_route_params' => ['id' => $comment->getId()],
            'csrf_token_id' => 'delete_comment_' . $comment->getId(),
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

        if (!$currentUser instanceof Users || $ownerId === null || $currentUser->getId() !== $ownerId) {
            throw $this->createAccessDeniedException();
        }
    }

    private function getCurrentUserEntity(): Users
    {
        $user = $this->getUser();
        if (!$user instanceof Users) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function buildPostModerationText(ForumPost $post): string
    {
        return trim($post->getTitle() . "\n" . $post->getContent());
    }

    private function normalizeTag(?string $value): ?string
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        if ($text[0] === '#') {
            $text = substr($text, 1);
        }

        $text = preg_replace('/[^A-Za-z0-9_-]+/', '', str_replace(' ', '', $text)) ?? '';
        if ($text === '') {
            return null;
        }

        return '#' . $text;
    }

    private function messageForPostStatus(\App\Service\Forum\AI\ModerationReport $report, bool $editing): string
    {
        return match (strtoupper($report->getDecision())) {
            'APPROVED' => $editing ? 'Post updated and approved.' : 'Post published.',
            'REJECTED' => $editing ? 'Post updated but rejected by moderation.' : 'Post rejected by moderation.',
            default => $editing ? 'Post updated and sent for manual review.' : 'Post submitted for moderation.',
        };
    }

    private function messageForCommentStatus(\App\Service\Forum\AI\ModerationReport $report, bool $editing): string
    {
        return match (strtoupper($report->getDecision())) {
            'APPROVED' => $editing ? 'Comment updated and approved.' : 'Comment added.',
            'REJECTED' => $editing ? 'Comment updated but rejected by moderation.' : 'Comment rejected by moderation.',
            default => $editing ? 'Comment updated and sent for moderation review.' : 'Comment submitted for moderation.',
        };
    }

    private function isAntiSpamRejected(FormInterface $form): bool
    {
        $lastResult = AntiSpam::getLastResult();

        return $lastResult !== null
            && $lastResult->getForm() === $form
            && $lastResult->isSpam();
    }
}



