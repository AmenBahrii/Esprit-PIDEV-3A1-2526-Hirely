<?php

namespace App\Controller\Admin;

use App\Entity\ForumComment;
use App\Entity\ForumPost;
use App\Entity\User;
use App\Form\ForumCommentType;
use App\Form\ForumPostType;
use App\Repository\ForumCommentRepository;
use App\Repository\ForumPostRepository;
use App\Service\Forum\Admin\ModerationFeedbackService;
use App\Service\Forum\AI\ModerationEngine;
use App\Service\Forum\AI\ModerationNoteFormatter;
use App\Service\Forum\GeminiBotService;
use App\Service\Forum\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/forum')]
class AdminForumController extends AbstractController
{
    #[Route('', name: 'admin_forum_dashboard')]
    public function dashboard(
        Request $request,
        ForumPostRepository $postRepository,
        ForumCommentRepository $commentRepository,
        PaginatorInterface $paginator
    ): Response {
        $status = (string) $request->query->get('status', 'ALL');
        $sort = (string) $request->query->get('sort', 'new');
        $search = $request->query->get('q');
        $pendingPosts = $postRepository->count(['status' => 'PENDING']);
        $pendingComments = $commentRepository->count(['status' => 'PENDING']);
        $posts = $paginator->paginate(
            $postRepository->createAdminFeedQueryBuilder(is_string($search) ? $search : null, $status, $sort),
            $request->query->getInt('page', 1),
            9,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'page']
        );
        $postItems = $posts->getItems();
        if (is_array($postItems)) {
            $postRepository->applyComputedMetrics($postItems, true, $this->getCurrentUserEntity());
        }

        return $this->render('admin/dashboard.html.twig', [
            'posts' => $posts,
            'status' => $status,
            'sort' => $sort,
            'search' => is_string($search) ? $search : '',
            'pending_posts' => $pendingPosts,
            'pending_comments' => $pendingComments,
        ]);
    }

    #[Route('/comments', name: 'admin_forum_comments')]
    public function comments(
        Request $request,
        ForumCommentRepository $commentRepository,
        PaginatorInterface $paginator
    ): Response {
        $status = (string) $request->query->get('status', 'ALL');
        $sort = (string) $request->query->get('sort', 'new');
        $search = $request->query->get('q');
        $comments = $paginator->paginate(
            $commentRepository->createAdminFeedQueryBuilder(is_string($search) ? $search : null, $status, $sort),
            $request->query->getInt('commentPage', 1),
            12,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'commentPage']
        );
        $commentItems = $comments->getItems();
        if (is_array($commentItems)) {
            $commentRepository->applyComputedMetrics($commentItems, $this->getCurrentUserEntity());
        }

        return $this->render('admin/comments.html.twig', [
            'comments' => $comments,
            'status' => $status,
            'sort' => $sort,
            'search' => is_string($search) ? $search : '',
        ]);
    }

    #[Route('/feedback', name: 'admin_forum_feedback')]
    public function feedback(Request $request, ModerationFeedbackService $feedbackService): Response
    {
        $filter = (string) $request->query->get('filter', 'all');
        $rows = $feedbackService->buildRows($filter);
        $postCount = count(array_filter($rows, static fn ($row) => $row->getType() === 'Post'));
        $commentCount = count(array_filter($rows, static fn ($row) => $row->getType() === 'Comment'));
        $fallbackCount = count(array_filter($rows, static fn ($row) => $row->getFallback() === 'Yes'));

        return $this->render('admin/feedback.html.twig', [
            'rows' => $rows,
            'filter' => $filter,
            'post_count' => $postCount,
            'comment_count' => $commentCount,
            'fallback_count' => $fallbackCount,
        ]);
    }

    #[Route('/feedback/export', name: 'admin_forum_feedback_export')]
    public function exportFeedback(Request $request, ModerationFeedbackService $feedbackService): StreamedResponse
    {
        $filter = (string) $request->query->get('filter', 'all');
        $rows = $feedbackService->buildRows($filter);

        $response = new StreamedResponse(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Time', 'Target', 'Decision', 'Category', 'Toxicity', 'Quality', 'Duplicate', 'Link Threat', 'Fallback']);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->getCreatedAt()->format('d M Y H:i'),
                    $row->getTarget(),
                    $row->getDecision(),
                    $row->getCategory(),
                    $row->getToxicity(),
                    $row->getQuality(),
                    $row->getDuplicate(),
                    $row->getLinkThreat(),
                    $row->getFallback(),
                ]);
            }
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="ai-feedback.csv"');

        return $response;
    }

    #[Route('/post/{id}', name: 'admin_forum_post_show', requirements: ['id' => '\d+'])]
    public function showPost(ForumPost $post, Request $request, ForumCommentRepository $commentRepository, PaginatorInterface $paginator): Response
    {
        $commentSort = (string) $request->query->get('comments', 'new');
        $comment = (new ForumComment())
            ->setStatus('PENDING');
        $currentUser = $this->getCurrentUserEntity();
        $comments = $paginator->paginate(
            $commentRepository->createForPostQueryBuilder($post, true, $commentSort, $currentUser),
            $request->query->getInt('commentPage', 1),
            10,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'commentPage']
        );
        $commentItems = $comments->getItems();
        if (is_array($commentItems)) {
            $commentRepository->applyComputedMetrics($commentItems, $currentUser);
        }
        $form = $this->createForm(ForumCommentType::class, $comment, [
            'is_admin' => true,
            'action' => $this->generateUrl('admin_forum_comment_new', [
                'id' => $post->getId(),
                'comments' => $commentSort,
                'commentPage' => $comments->getCurrentPageNumber(),
            ]),
        ]);

        return $this->render('admin/post_show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_sort' => $commentSort,
            'comment_form' => $form->createView(),
        ]);
    }

    #[Route('/post/{id}/edit', name: 'admin_forum_post_edit', requirements: ['id' => '\d+'])]
    public function editPost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response {
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

    #[Route('/post/{id}/delete', name: 'admin_forum_post_delete_confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirmDeletePost(ForumPost $post): Response
    {
        return $this->render('admin/confirm_delete.html.twig', [
            'subject' => 'post',
            'title' => 'Delete post',
            'message' => 'This will permanently remove the post, all comments, and all forum interactions attached to it.',
            'cancel_route' => 'admin_forum_post_show',
            'cancel_route_params' => ['id' => $post->getId()],
            'delete_route' => 'admin_forum_post_delete',
            'delete_route_params' => ['id' => $post->getId()],
            'csrf_token_id' => 'delete_post_' . $post->getId(),
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

    #[Route('/post/{id}/comment/new', name: 'admin_forum_comment_new', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function newComment(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        GeminiBotService $geminiBotService,
        ForumCommentRepository $commentRepository,
        PaginatorInterface $paginator
    ): Response {
        $comment = (new ForumComment())->setStatus('PENDING');
        $commentSort = (string) $request->query->get('comments', 'new');
        $commentPage = $request->query->getInt('commentPage', 1);
        $form = $this->createForm(ForumCommentType::class, $comment, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment
                ->setPost($post)
                ->setAuthor($this->getCurrentUserEntity())
                ->setCreatedAt(new \DateTimeImmutable())
                ->setUpdatedAt(new \DateTimeImmutable());

            $entityManager->persist($comment);
            $entityManager->flush();

            if ($geminiBotService->shouldReplyToComment($comment)) {
                $geminiBotService->createReplyForComment($comment);
            }

            $this->addFlash('success', 'Comment added.');

            return $this->redirectToRoute('admin_forum_post_show', ['id' => $post->getId()]);
        }

        $comments = $paginator->paginate(
            $commentRepository->createForPostQueryBuilder($post, true, $commentSort, $this->getCurrentUserEntity()),
            $commentPage,
            10,
            [PaginatorInterface::PAGE_PARAMETER_NAME => 'commentPage']
        );
        $commentItems = $comments->getItems();
        if (is_array($commentItems)) {
            $commentRepository->applyComputedMetrics($commentItems, $this->getCurrentUserEntity());
        }

        return $this->render('admin/post_show.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'comment_sort' => $commentSort,
            'comment_form' => $form->createView(),
        ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
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

    #[Route('/comment/{id}/delete', name: 'admin_forum_comment_delete_confirm', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function confirmDeleteComment(ForumComment $comment): Response
    {
        return $this->render('admin/confirm_delete.html.twig', [
            'subject' => 'comment',
            'title' => 'Delete comment',
            'message' => 'This will permanently remove the selected comment and its likes from the thread.',
            'cancel_route' => 'admin_forum_post_show',
            'cancel_route_params' => ['id' => $comment->getPost()?->getId()],
            'delete_route' => 'admin_forum_comment_delete',
            'delete_route_params' => ['id' => $comment->getId()],
            'csrf_token_id' => 'delete_comment_' . $comment->getId(),
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

    #[Route('/comment/{id}/pin', name: 'admin_forum_comment_pin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleCommentPin(ForumComment $comment, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('pin_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            $comment->setIsPinned(!$comment->isPinned());
            $comment->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', $comment->isPinned() ? 'Comment pinned.' : 'Comment unpinned.');
        }

        return $this->redirectToRoute('admin_forum_post_show', ['id' => $comment->getPost()?->getId()]);
    }

    #[Route('/post/{id}/ai/analyze', name: 'admin_forum_post_ai_analyze', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function analyzePost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        if (!$this->isCsrfTokenValid('ai_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_forum_post_show', ['id' => $post->getId()]);
        }

        $report = $moderationEngine->analyzePost($this->buildPostText($post), sprintf('post:%d', $post->getId() ?? 0));
        $post
            ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
            ->setEditedBy($this->getCurrentUserEntity())
            ->setEditedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();
        $this->addFlash('success', 'AI analysis saved for the post.');

        return $this->redirectToRoute('admin_forum_post_show', ['id' => $post->getId()]);
    }

    #[Route('/post/{id}/ai/reclassify', name: 'admin_forum_post_ai_reclassify', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reclassifyPost(
        ForumPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        if (!$this->isCsrfTokenValid('ai_reclassify_post_' . $post->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_forum_post_show', ['id' => $post->getId()]);
        }

        $report = $moderationEngine->analyzePost($this->buildPostText($post), sprintf('post:%d', $post->getId() ?? 0));
        $post
            ->setTag($this->normalizeTag($report->getPredictedCategory()) ?? $post->getTag())
            ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
            ->setEditedBy($this->getCurrentUserEntity())
            ->setEditedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();
        $this->addFlash('success', 'AI reclassify updated the post tag.');

        return $this->redirectToRoute('admin_forum_post_show', ['id' => $post->getId()]);
    }

    #[Route('/comment/{id}/ai/analyze', name: 'admin_forum_comment_ai_analyze', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function analyzeComment(
        ForumComment $comment,
        Request $request,
        EntityManagerInterface $entityManager,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        if (!$this->isCsrfTokenValid('ai_comment_' . $comment->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_forum_post_show', ['id' => $comment->getPost()?->getId()]);
        }

        $report = $moderationEngine->analyzeComment($comment->getContent(), sprintf('comment:%d', $comment->getId() ?? 0));
        $comment
            ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
            ->setEditedBy($this->getCurrentUserEntity())
            ->setEditedAt(new \DateTimeImmutable())
            ->setUpdatedAt(new \DateTimeImmutable());

        $entityManager->flush();
        $this->addFlash('success', 'AI analysis saved for the comment.');

        return $this->redirectToRoute('admin_forum_post_show', ['id' => $comment->getPost()?->getId()]);
    }

    #[Route('/ai/audit', name: 'admin_forum_ai_audit', methods: ['POST'])]
    public function auditLatest(
        Request $request,
        ForumPostRepository $postRepository,
        ForumCommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        ModerationEngine $moderationEngine,
        ModerationNoteFormatter $noteFormatter
    ): Response {
        if (!$this->isCsrfTokenValid('ai_audit', (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_forum_dashboard');
        }

        foreach ($postRepository->findLatestForAudit(50) as $post) {
            if (!$post instanceof ForumPost) {
                continue;
            }
            $report = $moderationEngine->analyzePost($this->buildPostText($post), sprintf('post:%d', $post->getId() ?? 0));
            $post
                ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
                ->setUpdatedAt(new \DateTimeImmutable());
        }

        foreach ($commentRepository->findLatestForAudit(50) as $comment) {
            if (!$comment instanceof ForumComment) {
                continue;
            }
            $report = $moderationEngine->analyzeComment($comment->getContent(), sprintf('comment:%d', $comment->getId() ?? 0));
            $comment
                ->setModerationNote($noteFormatter->buildSimpleAiNote($report))
                ->setUpdatedAt(new \DateTimeImmutable());
        }

        $entityManager->flush();
        $this->addFlash('success', 'AI audit completed for the latest non-approved posts and comments.');

        return $this->redirectToRoute('admin_forum_feedback');
    }

    private function getCurrentUserEntity(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function buildPostText(ForumPost $post): string
    {
        return trim($post->getTitle() . "\n" . $post->getContent());
    }

    private function normalizeTag(string $value): ?string
    {
        $text = trim($value);
        if ($text === '') {
            return null;
        }
        if ($text[0] === '#') {
            $text = substr($text, 1);
        }

        $text = preg_replace('/[^A-Za-z0-9_-]+/', '', str_replace(' ', '', $text)) ?? '';

        return $text !== '' ? '#' . $text : null;
    }
}
