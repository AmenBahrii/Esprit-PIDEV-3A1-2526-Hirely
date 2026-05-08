<?php

namespace App\Repository;

use App\Entity\ForumInteraction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ForumInteractionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ForumInteraction::class);
    }

    public function togglePostLike(int $postId, User $user): bool
    {
        return $this->toggleLike(ForumInteraction::TARGET_POST, $postId, $user);
    }

    public function toggleCommentLike(int $commentId, User $user): bool
    {
        return $this->toggleLike(ForumInteraction::TARGET_COMMENT, $commentId, $user);
    }

    private function toggleLike(string $targetType, int $targetId, User $user): bool
    {
        $em = $this->getEntityManager();
        $existing = $this->findOneBy([
            'targetType' => $targetType,
            'targetId' => $targetId,
            'interactionType' => ForumInteraction::TYPE_LIKE,
            'user' => $user,
        ]);

        if ($existing instanceof ForumInteraction) {
            $em->remove($existing);
            $em->flush();

            return false;
        }

        $interaction = (new ForumInteraction())
            ->setTargetType($targetType)
            ->setTargetId($targetId)
            ->setInteractionType(ForumInteraction::TYPE_LIKE)
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable());

        $em->persist($interaction);
        $em->flush();

        return true;
    }
}
