<?php

namespace App\Repository;

use App\Entity\ForumInteraction;
use App\Entity\ForumPost;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ForumPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumPost::class);
    }

    public function findFeed(?string $search, string $sort, ?string $tag = null, ?User $currentUser = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'APPROVED');

        $this->applySearch($qb, $search);
        $this->applyTagFilter($qb, $tag);
        $this->applySort($qb, $sort);

        $posts = $qb->getQuery()->getResult();
        $this->hydrateComputedMetrics($posts, false, $currentUser);

        return $posts;
    }

    public function findAdminFeed(?string $search, ?string $status, string $sort, ?User $currentUser = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a');

        if ($status !== null && $status !== '' && strtoupper($status) !== 'ALL') {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', strtoupper($status));
        }

        $this->applySearch($qb, $search);
        $this->applySort($qb, $sort);

        $posts = $qb->getQuery()->getResult();
        $this->hydrateComputedMetrics($posts, true, $currentUser);

        return $posts;
    }

    public function findByAuthorForProfile(User $author, bool $includeHidden, string $sort = 'new', ?User $currentUser = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->andWhere('p.author = :author')
            ->setParameter('author', $author);

        if (!$includeHidden) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', 'APPROVED');
        }

        if (strtolower($sort) === 'old') {
            $qb->addOrderBy('p.createdAt', 'ASC');
        } else {
            $qb->addOrderBy('p.createdAt', 'DESC');
        }

        $posts = $qb->getQuery()->getResult();
        $this->hydrateComputedMetrics($posts, $includeHidden, $currentUser);

        return $posts;
    }

    public function findOneForDisplay(int $id, ?User $viewer = null): ?ForumPost
    {
        $post = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')
            ->addSelect('a')
            ->leftJoin('p.editedBy', 'e')
            ->addSelect('e')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        if ($post === null) {
            return null;
        }

        $canSee = $post->getStatus() === 'APPROVED'
            || ($viewer !== null && $viewer->getId() === $post->getAuthor()?->getId())
            || ($viewer !== null && in_array('ROLE_ADMIN', $viewer->getRoles(), true));

        if (!$canSee) {
            return null;
        }

        $this->hydrateComputedMetrics([$post], $viewer !== null && in_array('ROLE_ADMIN', $viewer->getRoles(), true), $viewer);

        return $post;
    }

    public function applyComputedMetrics(array $posts, bool $includeHiddenComments = false, ?User $currentUser = null): void
    {
        $this->hydrateComputedMetrics($posts, $includeHiddenComments, $currentUser);
    }

    public function getFeedTags(): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('DISTINCT p.tag AS tag')
            ->andWhere('p.status = :status')
            ->andWhere('p.tag IS NOT NULL')
            ->andWhere('TRIM(p.tag) <> :blank')
            ->setParameter('status', 'APPROVED')
            ->setParameter('blank', '')
            ->orderBy('p.tag', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_values(array_filter(array_map(
            static fn (array $row): ?string => isset($row['tag']) ? trim((string) $row['tag']) : null,
            $rows
        )));
    }

    public function hasRecentDuplicateByAuthor(User $author, string $title, string $content, int $windowMinutes = 10): bool
    {
        $normalizedTitle = $this->normalizeText($title);
        $normalizedContent = $this->normalizeText($content);

        if ($normalizedTitle === '' || $normalizedContent === '') {
            return false;
        }

        $threshold = new \DateTimeImmutable(sprintf('-%d minutes', max(1, $windowMinutes)));
        $count = (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.author = :author')
            ->andWhere('LOWER(TRIM(p.title)) = :title')
            ->andWhere('LOWER(TRIM(p.content)) = :content')
            ->andWhere('p.createdAt >= :threshold')
            ->setParameter('author', $author)
            ->setParameter('title', $normalizedTitle)
            ->setParameter('content', $normalizedContent)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    private function applySearch(QueryBuilder $qb, ?string $search): void
    {
        $search = trim((string) $search);
        if ($search === '') {
            return;
        }

        $qb->andWhere('LOWER(p.title) LIKE :term OR LOWER(p.content) LIKE :term OR LOWER(COALESCE(p.tag, \'\')) LIKE :term OR LOWER(CONCAT(COALESCE(a.firstName, \'\'), \' \', COALESCE(a.lastName, \'\'))) LIKE :term')
            ->setParameter('term', '%' . strtolower($search) . '%');
    }

    private function applyTagFilter(QueryBuilder $qb, ?string $tag): void
    {
        $tag = trim((string) $tag);
        if ($tag === '' || strtolower($tag) === 'all') {
            return;
        }

        if ($tag[0] !== '#') {
            $tag = '#' . $tag;
        }

        $qb->andWhere('LOWER(COALESCE(p.tag, \'\')) = :tag')
            ->setParameter('tag', strtolower($tag));
    }

    private function applySort(QueryBuilder $qb, string $sort): void
    {
        $sort = strtolower($sort);
        $qb->addOrderBy('p.isPinned', 'DESC');

        if ($sort === 'old') {
            $qb->addOrderBy('p.createdAt', 'ASC');
            return;
        }

        $qb->addOrderBy('p.createdAt', 'DESC');
    }

    private function normalizeText(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value) ?? ''));
    }

    private function hydrateComputedMetrics(array $posts, bool $includeHiddenComments, ?User $currentUser): void
    {
        if ($posts === []) {
            return;
        }

        $ids = array_values(array_filter(array_map(
            static fn (ForumPost $post): ?int => $post->getId(),
            $posts
        )));

        if ($ids === []) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $idSql = implode(',', array_map('intval', $ids));

        $likeCounts = [];
        $commentCounts = [];
        $likedIds = [];

        $likeRows = $connection->fetchAllAssociative(
            "SELECT target_id, COUNT(*) AS total
             FROM forum_interaction
             WHERE target_type = :targetType
               AND interaction_type = :interactionType
               AND target_id IN ($idSql)
             GROUP BY target_id",
            [
                'targetType' => ForumInteraction::TARGET_POST,
                'interactionType' => ForumInteraction::TYPE_LIKE,
            ]
        );

        foreach ($likeRows as $row) {
            $likeCounts[(int) $row['target_id']] = (int) $row['total'];
        }

        $commentSql = $includeHiddenComments
            ? "SELECT post_id, COUNT(*) AS total FROM forum_comment WHERE post_id IN ($idSql) GROUP BY post_id"
            : "SELECT post_id, COUNT(*) AS total FROM forum_comment WHERE status = 'APPROVED' AND post_id IN ($idSql) GROUP BY post_id";

        foreach ($connection->fetchAllAssociative($commentSql) as $row) {
            $commentCounts[(int) $row['post_id']] = (int) $row['total'];
        }

        if ($currentUser?->getId() !== null) {
            $likedRows = $connection->fetchFirstColumn(
                "SELECT target_id
                 FROM forum_interaction
                 WHERE target_type = :targetType
                   AND interaction_type = :interactionType
                   AND user_id = :userId
                   AND target_id IN ($idSql)",
                [
                    'targetType' => ForumInteraction::TARGET_POST,
                    'interactionType' => ForumInteraction::TYPE_LIKE,
                    'userId' => $currentUser->getId(),
                ]
            );

            $likedIds = array_map('intval', $likedRows);
        }

        foreach ($posts as $post) {
            $postId = (int) $post->getId();
            $post->setLikeCount($likeCounts[$postId] ?? 0);
            $post->setCommentCount($commentCounts[$postId] ?? 0);
            $post->setLikedByCurrentUser(in_array($postId, $likedIds, true));
        }
    }
}
