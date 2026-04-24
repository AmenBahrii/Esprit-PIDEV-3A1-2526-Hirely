<?php

namespace App\Repository;

use App\Entity\ForumComment;
use App\Entity\ForumNotification;
use App\Entity\ForumPost;
use App\Entity\Users;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ForumNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumNotification::class);
    }

    public function findLatestForUser(Users $recipient, int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.actor', 'a')
            ->addSelect('a')
            ->leftJoin('n.post', 'p')
            ->addSelect('p')
            ->leftJoin('n.comment', 'c')
            ->addSelect('c')
            ->andWhere('n.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->addOrderBy('n.createdAt', 'DESC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getResult();
    }

    public function createHistoryQueryBuilder(Users $recipient): QueryBuilder
    {
        return $this->createQueryBuilder('n')
            ->leftJoin('n.actor', 'a')
            ->addSelect('a')
            ->leftJoin('n.post', 'p')
            ->addSelect('p')
            ->leftJoin('n.comment', 'c')
            ->addSelect('c')
            ->andWhere('n.recipient = :recipient')
            ->setParameter('recipient', $recipient)
            ->addOrderBy('n.createdAt', 'DESC');
    }

    public function countUnreadForUser(Users $recipient): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :recipient')
            ->andWhere('n.isRead = :isRead')
            ->setParameter('recipient', $recipient)
            ->setParameter('isRead', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markReadForRecipient(ForumNotification $notification, Users $recipient): void
    {
        if ($notification->getRecipient()?->getId() !== $recipient->getId()) {
            return;
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $this->getEntityManager()->flush();
        }
    }

    public function markAllReadForUser(Users $recipient): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', ':isRead')
            ->andWhere('n.recipient = :recipient')
            ->setParameter('isRead', true)
            ->setParameter('recipient', $recipient)
            ->getQuery()
            ->execute();
    }

    public function hasRecentLikeNotification(
        Users $recipient,
        Users $actor,
        string $type,
        ?ForumPost $post,
        ?ForumComment $comment,
        int $cooldownMinutes
    ): bool {
        $threshold = new \DateTimeImmutable(sprintf('-%d minutes', max(1, $cooldownMinutes)));

        $qb = $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.recipient = :recipient')
            ->andWhere('n.actor = :actor')
            ->andWhere('n.type = :type')
            ->andWhere('(n.isRead = :isRead OR n.createdAt >= :threshold)')
            ->setParameter('recipient', $recipient)
            ->setParameter('actor', $actor)
            ->setParameter('type', $type)
            ->setParameter('threshold', $threshold)
            ->setParameter('isRead', false);

        if ($post instanceof ForumPost) {
            $qb->andWhere('n.post = :post')->setParameter('post', $post);
        } else {
            $qb->andWhere('n.post IS NULL');
        }

        if ($comment instanceof ForumComment) {
            $qb->andWhere('n.comment = :comment')->setParameter('comment', $comment);
        } else {
            $qb->andWhere('n.comment IS NULL');
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}



