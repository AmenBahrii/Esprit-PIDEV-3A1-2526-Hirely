<?php

namespace App\Repository;

use App\Entity\ForumComment;
use App\Entity\ForumInteraction;
use App\Entity\ForumPost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ForumCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumComment::class);
    }

    public function findForPost(ForumPost $post, bool $includeHidden, string $sort = 'new', ?User $viewer = null): array
    {
        $qb = $this->createForPostQueryBuilder($post, $includeHidden, $sort, $viewer);
        $comments = $qb->getQuery()->getResult();
        $this->hydrateLikeMetrics($comments, $viewer);
        $this->sortHydratedComments($comments, $sort);

        return $comments;
    }

    public function createForPostQueryBuilder(ForumPost $post, bool $includeHidden, string $sort = 'new', ?User $viewer = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.author', 'a')
            ->addSelect('a')
            ->leftJoin('c.editedBy', 'e')
            ->addSelect('e')
            ->andWhere('c.post = :post')
            ->setParameter('post', $post)
            ->addOrderBy('c.isPinned', 'DESC');

        if (!$includeHidden) {
            if ($viewer instanceof User) {
                $qb->andWhere('(c.status = :status OR c.author = :viewer)')
                    ->setParameter('status', 'APPROVED')
                    ->setParameter('viewer', $viewer);
            } else {
                $qb->andWhere('c.status = :status')
                    ->setParameter('status', 'APPROVED');
            }
        }

        $sort = strtolower($sort);
        if ($sort === 'top') {
            $qb
                ->addSelect('(SELECT COUNT(commentLike.id) FROM ' . ForumInteraction::class . ' commentLike WHERE commentLike.targetType = :commentLikeTarget AND commentLike.interactionType = :commentLikeType AND commentLike.targetId = c.id) AS HIDDEN commentLikeTotal')
                ->setParameter('commentLikeTarget', ForumInteraction::TARGET_COMMENT)
                ->setParameter('commentLikeType', ForumInteraction::TYPE_LIKE)
                ->addOrderBy('commentLikeTotal', 'DESC')
                ->addOrderBy('c.createdAt', 'DESC');

            return $qb;
        }

        $qb->addOrderBy('c.createdAt', $sort === 'old' ? 'ASC' : 'DESC');

        return $qb;
    }

    public function findRecentByAuthorForProfile(User $author, bool $includeHidden, int $limit = 15): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->addSelect('p')
            ->leftJoin('p.author', 'pa')
            ->addSelect('pa')
            ->andWhere('c.author = :author')
            ->setParameter('author', $author)
            ->addOrderBy('c.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit));

        if (!$includeHidden) {
            $qb->andWhere('c.status = :commentStatus')
                ->andWhere('p.status = :postStatus')
                ->setParameter('commentStatus', 'APPROVED')
                ->setParameter('postStatus', 'APPROVED');
        }

        return $qb->getQuery()->getResult();
    }

    public function hasRecentDuplicateForPostByAuthor(ForumPost $post, User $author, string $content, int $windowMinutes = 5): bool
    {
        $normalizedContent = $this->normalizeText($content);
        if ($normalizedContent === '') {
            return false;
        }

        $threshold = new \DateTimeImmutable(sprintf('-%d minutes', max(1, $windowMinutes)));
        $count = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.post = :post')
            ->andWhere('c.author = :author')
            ->andWhere('LOWER(TRIM(c.content)) = :content')
            ->andWhere('c.createdAt >= :threshold')
            ->setParameter('post', $post)
            ->setParameter('author', $author)
            ->setParameter('content', $normalizedContent)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function findLatestWithModerationNotes(int $limit = 250): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->addSelect('p')
            ->leftJoin('c.author', 'a')
            ->addSelect('a')
            ->andWhere('c.moderationNote IS NOT NULL')
            ->andWhere('TRIM(c.moderationNote) <> :blank')
            ->setParameter('blank', '')
            ->addOrderBy('c.updatedAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findLatestForAudit(int $limit = 50): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.post', 'p')
            ->addSelect('p')
            ->leftJoin('c.author', 'a')
            ->addSelect('a')
            ->andWhere('c.status <> :status')
            ->setParameter('status', 'APPROVED')
            ->addOrderBy('c.updatedAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function findAdminFeed(?string $search, ?string $status, string $sort, ?User $viewer = null): array
    {
        $qb = $this->createAdminFeedQueryBuilder($search, $status, $sort);
        $comments = $qb->getQuery()->getResult();
        $this->hydrateLikeMetrics($comments, $viewer);
        $this->sortHydratedComments($comments, $sort);

        return $comments;
    }

    public function createAdminFeedQueryBuilder(?string $search, ?string $status, string $sort): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.author', 'a')
            ->addSelect('a')
            ->leftJoin('c.post', 'p')
            ->addSelect('p')
            ->leftJoin('p.author', 'pa')
            ->addSelect('pa')
            ->leftJoin('c.editedBy', 'e')
            ->addSelect('e');

        if ($status !== null && $status !== '' && strtoupper($status) !== 'ALL') {
            $qb->andWhere('c.status = :status')
                ->setParameter('status', strtoupper($status));
        }

        $this->applyAdminSearch($qb, $search);
        $this->applyAdminSort($qb, $sort);

        return $qb;
    }

    public function applyComputedMetrics(array $comments, ?User $viewer): void
    {
        $this->hydrateLikeMetrics($comments, $viewer);
    }

    private function applyAdminSearch(QueryBuilder $qb, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $qb->andWhere('LOWER(c.content) LIKE :term OR LOWER(p.title) LIKE :term OR LOWER(CONCAT(COALESCE(a.firstName, \'\'), \' \', COALESCE(a.lastName, \'\'))) LIKE :term')
            ->setParameter('term', '%' . strtolower($search) . '%');
    }

    private function applyAdminSort(QueryBuilder $qb, string $sort): void
    {
        $safeSort = strtolower($sort);
        $qb->addOrderBy('c.isPinned', 'DESC');

        if ($safeSort === 'top') {
            $qb
                ->addSelect('(SELECT COUNT(commentLikeAdmin.id) FROM ' . ForumInteraction::class . ' commentLikeAdmin WHERE commentLikeAdmin.targetType = :commentLikeAdminTarget AND commentLikeAdmin.interactionType = :commentLikeAdminType AND commentLikeAdmin.targetId = c.id) AS HIDDEN commentLikeAdminTotal')
                ->setParameter('commentLikeAdminTarget', ForumInteraction::TARGET_COMMENT)
                ->setParameter('commentLikeAdminType', ForumInteraction::TYPE_LIKE)
                ->addOrderBy('commentLikeAdminTotal', 'DESC')
                ->addOrderBy('c.createdAt', 'DESC');

            return;
        }

        $qb->addOrderBy('c.createdAt', $safeSort === 'old' ? 'ASC' : 'DESC');
    }

    private function normalizeText(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
    }

    private function hydrateLikeMetrics(array $comments, ?User $viewer): void
    {
        if ($comments === []) {
            return;
        }

        $ids = array_values(array_filter(array_map(
            static fn (ForumComment $comment): ?int => $comment->getId(),
            $comments
        )));

        if ($ids === []) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $idSql = implode(',', array_map('intval', $ids));

        $likeCounts = [];
        $likedIds = [];

        $likeRows = $connection->fetchAllAssociative(
            "SELECT target_id, COUNT(*) AS total
             FROM forum_interaction
             WHERE target_type = :targetType
               AND interaction_type = :interactionType
               AND target_id IN ($idSql)
             GROUP BY target_id",
            [
                'targetType' => ForumInteraction::TARGET_COMMENT,
                'interactionType' => ForumInteraction::TYPE_LIKE,
            ]
        );

        foreach ($likeRows as $row) {
            $likeCounts[(int) $row['target_id']] = (int) $row['total'];
        }

        if ($viewer?->getId() !== null) {
            $likedRows = $connection->fetchFirstColumn(
                "SELECT target_id
                 FROM forum_interaction
                 WHERE target_type = :targetType
                   AND interaction_type = :interactionType
                   AND user_id = :userId
                   AND target_id IN ($idSql)",
                [
                    'targetType' => ForumInteraction::TARGET_COMMENT,
                    'interactionType' => ForumInteraction::TYPE_LIKE,
                    'userId' => $viewer->getId(),
                ]
            );

            $likedIds = array_map('intval', $likedRows);
        }

        foreach ($comments as $comment) {
            $commentId = (int) $comment->getId();
            $comment->setLikeCount($likeCounts[$commentId] ?? 0);
            $comment->setLikedByCurrentUser(in_array($commentId, $likedIds, true));
        }
    }

    private function sortHydratedComments(array &$comments, string $sort): void
    {
        if (strtolower($sort) !== 'top') {
            return;
        }

        usort($comments, static function (ForumComment $left, ForumComment $right): int {
            if ($left->isPinned() !== $right->isPinned()) {
                return $left->isPinned() ? -1 : 1;
            }

            $byLikes = $right->getLikeCount() <=> $left->getLikeCount();
            if ($byLikes !== 0) {
                return $byLikes;
            }

            return ($right->getCreatedAt()?->getTimestamp() ?? 0) <=> ($left->getCreatedAt()?->getTimestamp() ?? 0);
        });
    }
}
