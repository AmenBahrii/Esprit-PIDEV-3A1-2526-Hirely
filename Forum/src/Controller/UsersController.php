<?php

namespace App\Controller;

use App\Entity\Users;
use App\Form\UsersType;
use App\Service\UserRoleSummaryService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/users')]
final class UsersController extends AbstractController
{
    #[Route(name: 'app_users_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, UserRoleSummaryService $userRoleSummaryService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $entityManager->getRepository(Users::class)->findAll();
        $connection = $entityManager->getConnection();

        $groupedStats = $connection->fetchAllAssociative(
            'SELECT
                u.status AS status,
                COALESCE(r.name, "Unknown") AS roleName,
                COUNT(*) AS total
            FROM users u
            LEFT JOIN role r ON r.role_id = u.role_id
            GROUP BY u.status, r.name
            ORDER BY total DESC, roleName ASC'
        );

        $googleLinkedUsers = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM users WHERE google_id IS NOT NULL AND google_id <> ""'
        );

        $faceEnabledUsers = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM users WHERE face_data IS NOT NULL AND face_data <> ""'
        );

        $aiUserSummary = $userRoleSummaryService->generateSummary(
            array_map(static function (array $row): array {
                return [
                    'status' => (string) ($row['status'] ?? 'unknown'),
                    'roleName' => (string) ($row['roleName'] ?? 'Unknown'),
                    'total' => (int) ($row['total'] ?? 0),
                ];
            }, $groupedStats),
            count($users),
            $googleLinkedUsers,
            $faceEnabledUsers
        );

        return $this->render('users/index.html.twig', [
            'users' => $users,
            'ai_user_summary' => $aiUserSummary,
            'google_linked_users' => $googleLinkedUsers,
            'face_enabled_users' => $faceEnabledUsers,
        ]);
    }

    #[Route('/new', name: 'app_users_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new Users();
        $form = $this->createForm(UsersType::class, $user, [
            'is_admin' => true,
            'password_required' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );

            $user->setPassword($hashedPassword);

            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_users_index');
        }

        return $this->render('users/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_users_show', methods: ['GET'])]
    public function show(Users $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $vcard = $this->buildContactVcard($user);

        return $this->render('users/show.html.twig', [
            'user' => $user,
            'contact_qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($vcard),
            'contact_vcard' => $vcard,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_users_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Users $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $currentUser = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isAdmin && (!$currentUser instanceof Users || $currentUser->getId() !== $user->getId())) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(UsersType::class, $user, [
            'is_admin' => $isAdmin,
            'password_required' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (is_string($plainPassword) && $plainPassword !== '') {
                $hashedPassword = $passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                );
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            return $this->redirectToRoute(
                $isAdmin ? 'app_users_index' : 'app_joboffer_index'
            );
        }

        $vcard = $this->buildContactVcard($user);

        return $this->render('users/edit.html.twig', [
            'user' => $user,
            'form' => $form,
            'contact_qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($vcard),
            'contact_vcard' => $vcard,
        ]);
    }

    #[Route('/{id}', name: 'app_users_delete', methods: ['POST'])]
    public function delete(Request $request, Users $user, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_users_index', [], Response::HTTP_SEE_OTHER);
    }

    private function escapeVcardValue(?string $value): string
    {
        $sanitized = str_replace(["\r\n", "\r", "\n"], '\n', (string) $value);

        return str_replace(['\\', ';', ','], ['\\\\', '\;', '\,'], $sanitized);
    }

    private function buildContactVcard(Users $user): string
    {
        $fullName = trim(sprintf('%s %s', $user->getFirstName(), $user->getLastName()));
        $roleName = $user->getRole()?->getName() ?? 'User';

        return implode("\r\n", [
            'BEGIN:VCARD',
            'VERSION:3.0',
            sprintf('N:%s;%s;;;', $this->escapeVcardValue($user->getLastName()), $this->escapeVcardValue($user->getFirstName())),
            sprintf('FN:%s', $this->escapeVcardValue($fullName !== '' ? $fullName : $user->getEmail())),
            sprintf('EMAIL;TYPE=INTERNET:%s', $this->escapeVcardValue($user->getEmail())),
            'ORG:Hirely',
            sprintf('TITLE:%s', $this->escapeVcardValue(ucfirst($roleName))),
            sprintf('NOTE:%s', $this->escapeVcardValue(sprintf('Hirely account status: %s', ucfirst($user->getStatus())))),
            'END:VCARD',
        ]);
    }
}
