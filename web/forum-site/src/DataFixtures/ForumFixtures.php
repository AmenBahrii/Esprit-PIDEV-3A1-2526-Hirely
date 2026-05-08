<?php

namespace App\DataFixtures;

use App\Entity\ForumComment;
use App\Entity\ForumInteraction;
use App\Entity\ForumNotification;
use App\Entity\ForumPost;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ForumFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $now = new \DateTimeImmutable();

        $userRole = $this->role($manager, 'user', 'Forum user', $now);
        $adminRole = $this->role($manager, 'admin', 'Forum moderator', $now);

        $alice = $this->user($manager, 'forum.alice@hirely.local', 'Alice', 'Candidate', $userRole);
        $bob = $this->user($manager, 'forum.bob@hirely.local', 'Bob', 'Recruiter', $userRole);
        $admin = $this->user($manager, 'forum.admin@hirely.local', 'Maya', 'Moderator', $adminRole);
        foreach ([$alice, $bob, $admin] as $user) {
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password'));
            $manager->persist($user);
        }
        $manager->flush();

        if ($this->forumSeedAlreadyLoaded($manager)) {
            return;
        }

        $approvedPost = (new ForumPost())
            ->setAuthor($alice)
            ->setTitle('Symfony internship interview tips')
            ->setContent('I have a Symfony internship interview next week. What should I revise first?')
            ->setTag('#Career')
            ->setStatus('APPROVED')
            ->setModerationNote('AI: Duplicate=0.00 | Toxicity=0.02 | Relevance=0.91 | Quality=0.80 | LinkThreat=NONE (Career) | Decision=APPROVED')
            ->setIsPinned(true)
            ->setIsLocked(false)
            ->setCreatedAt($now->modify('-3 days'))
            ->setUpdatedAt($now->modify('-3 days'));

        $pendingPost = (new ForumPost())
            ->setAuthor($bob)
            ->setTitle('Remote onboarding question')
            ->setContent('Can a recruiter share a clean onboarding checklist for remote internship candidates?')
            ->setTag('#Onboarding')
            ->setStatus('PENDING')
            ->setModerationNote('AI: Duplicate=0.20 | Toxicity=0.05 | Relevance=0.70 | Quality=0.55 | LinkThreat=NONE (Onboarding) | Decision=PENDING')
            ->setIsPinned(false)
            ->setIsLocked(false)
            ->setCreatedAt($now->modify('-2 days'))
            ->setUpdatedAt($now->modify('-2 days'));

        $rejectedPost = (new ForumPost())
            ->setAuthor($bob)
            ->setTitle('Suspicious external offer')
            ->setContent('This post intentionally represents spam-like data for moderation demonstration.')
            ->setTag('#General')
            ->setStatus('REJECTED')
            ->setModerationNote('AI: Duplicate=0.10 | Toxicity=0.84 | Relevance=0.20 | Quality=0.10 | LinkThreat=FLAGGED (General) | Decision=REJECTED')
            ->setIsPinned(false)
            ->setIsLocked(true)
            ->setCreatedAt($now->modify('-1 day'))
            ->setUpdatedAt($now->modify('-1 day'));

        foreach ([$approvedPost, $pendingPost, $rejectedPost] as $post) {
            $manager->persist($post);
        }
        $manager->flush();

        $comment = (new ForumComment())
            ->setPost($approvedPost)
            ->setAuthor($bob)
            ->setContent('@gemini give me a concise checklist for this interview')
            ->setStatus('APPROVED')
            ->setModerationNote('AI: Duplicate=0.00 | Toxicity=0.01 | Relevance=0.86 | Quality=0.75 | LinkThreat=NONE (Career) | Decision=APPROVED')
            ->setCreatedAt($now->modify('-2 days'))
            ->setUpdatedAt($now->modify('-2 days'));

        $adminComment = (new ForumComment())
            ->setPost($approvedPost)
            ->setAuthor($admin)
            ->setContent('Review routing, services, forms, validation, Doctrine repositories, and security voters/access checks.')
            ->setStatus('APPROVED')
            ->setModerationNote('AI: Duplicate=0.00 | Toxicity=0.01 | Relevance=0.95 | Quality=0.88 | LinkThreat=NONE (Career) | Decision=APPROVED')
            ->setIsPinned(true)
            ->setCreatedAt($now->modify('-1 day'))
            ->setUpdatedAt($now->modify('-1 day'));

        $manager->persist($comment);
        $manager->persist($adminComment);
        $manager->flush();

        $manager->persist($this->like(ForumInteraction::TARGET_POST, (int) $approvedPost->getId(), $bob, $now));
        $manager->persist($this->like(ForumInteraction::TARGET_COMMENT, (int) $adminComment->getId(), $alice, $now));

        $manager->persist((new ForumNotification())
            ->setRecipient($alice)
            ->setActor($bob)
            ->setType(ForumNotification::TYPE_POST_LIKED)
            ->setPost($approvedPost)
            ->setMessage('Bob Recruiter liked your post (#' . $approvedPost->getId() . ').')
            ->setIsRead(false)
            ->setCreatedAt($now));

        $manager->persist((new ForumNotification())
            ->setRecipient($bob)
            ->setActor($admin)
            ->setType(ForumNotification::TYPE_POST_STATUS_CHANGED)
            ->setPost($pendingPost)
            ->setMessage('Admin reviewed your post (#' . $pendingPost->getId() . ') and left it pending.')
            ->setIsRead(false)
            ->setCreatedAt($now));

        $manager->flush();
    }

    private function role(ObjectManager $manager, string $name, string $description, \DateTimeInterface $createdAt): Role
    {
        $role = $manager->getRepository(Role::class)->findOneBy(['name' => $name]);
        if (!$role instanceof Role) {
            $role = (new Role())->setName($name);
        }

        $role
            ->setDescription($description)
            ->setStatus('active')
            ->setDefaultDashboard($name === 'admin' ? 'admin_forum_dashboard' : 'forum_index');

        if ($role->getCreatedAt() === null) {
            $role->setCreatedAt($createdAt);
        }

        $manager->persist($role);

        return $role;
    }

    private function user(ObjectManager $manager, string $email, string $firstName, string $lastName, Role $role): User
    {
        $user = $manager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user instanceof User) {
            $user = (new User())->setEmail($email);
        }

        return $user
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRole($role)
            ->setStatus('active')
            ->setIsVerified(true);
    }

    private function like(string $targetType, int $targetId, User $user, \DateTimeInterface $createdAt): ForumInteraction
    {
        return (new ForumInteraction())
            ->setTargetType($targetType)
            ->setTargetId($targetId)
            ->setInteractionType(ForumInteraction::TYPE_LIKE)
            ->setUser($user)
            ->setCreatedAt($createdAt);
    }

    private function forumSeedAlreadyLoaded(ObjectManager $manager): bool
    {
        return $manager->getRepository(ForumPost::class)->findOneBy([
            'title' => 'Symfony internship interview tips',
        ]) instanceof ForumPost;
    }
}
