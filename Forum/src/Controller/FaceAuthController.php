<?php

namespace App\Controller;

use App\Entity\Users;
use App\Security\AppCustomAuthenticator;
use App\Service\FaceRecognitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/face-auth')]
final class FaceAuthController extends AbstractController
{
    #[Route('/login', name: 'app_face_auth_login', methods: ['POST'])]
    public function login(
        Request $request,
        EntityManagerInterface $entityManager,
        FaceRecognitionService $faceRecognitionService,
        Security $security
    ): JsonResponse {
        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || !$this->isCsrfTokenValid('face_login', (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'success' => false,
                'message' => 'The face sign-in request is invalid.',
            ], 400);
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $descriptor = $payload['descriptor'] ?? null;

        if ($email === '' || !is_array($descriptor)) {
            return $this->json([
                'success' => false,
                'message' => 'Email and face data are both required.',
            ], 422);
        }

        $user = $entityManager->getRepository(Users::class)->findOneBy([
            'email' => $email,
        ]);

        if (!$user instanceof Users || $user->getStatus() !== 'active') {
            return $this->json([
                'success' => false,
                'message' => 'No active user was found for that email.',
            ], 404);
        }

        if ($user->getFaceData() === null || trim((string) $user->getFaceData()) === '') {
            return $this->json([
                'success' => false,
                'message' => 'You need to set up face recognition first.',
            ], 409);
        }

        if (!$faceRecognitionService->matchesStoredDescriptor($user->getFaceData(), $descriptor)) {
            return $this->json([
                'success' => false,
                'message' => 'Face verification failed. Try again in good lighting and face the camera directly.',
            ], 401);
        }

        $security->login($user, AppCustomAuthenticator::class, 'main');

        $redirect = $this->generateUrl('forum_index');
        $roles = $user->getRoles();
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $redirect = $this->generateUrl('admin_forum_dashboard');
        } elseif (in_array('ROLE_RECRUITER', $roles, true) || in_array('ROLE_CANDIDATE', $roles, true)) {
            $redirect = $this->generateUrl('app_home');
        }

        return $this->json([
            'success' => true,
            'redirect' => $redirect,
        ]);
    }

    #[Route('/users/{id}/enroll', name: 'app_face_auth_enroll', methods: ['POST'])]
    public function enroll(
        Request $request,
        Users $user,
        EntityManagerInterface $entityManager,
        FaceRecognitionService $faceRecognitionService
    ): JsonResponse {
        $this->denyFaceAccess($user);

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || !$this->isCsrfTokenValid('face_enroll_' . $user->getId(), (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'success' => false,
                'message' => 'The face enrollment request is invalid.',
            ], 400);
        }

        $descriptor = $payload['descriptor'] ?? null;
        if (!is_array($descriptor)) {
            return $this->json([
                'success' => false,
                'message' => 'No face descriptor was captured.',
            ], 422);
        }

        try {
            $user->setFaceData($faceRecognitionService->serializeDescriptor($descriptor));
            $entityManager->flush();
        } catch (\Throwable) {
            return $this->json([
                'success' => false,
                'message' => 'Unable to save the captured face data.',
            ], 422);
        }

        return $this->json([
            'success' => true,
            'message' => 'Face ID saved successfully.',
        ]);
    }

    #[Route('/users/{id}/remove', name: 'app_face_auth_remove', methods: ['POST'])]
    public function remove(
        Request $request,
        Users $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyFaceAccess($user);

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || !$this->isCsrfTokenValid('face_remove_' . $user->getId(), (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'success' => false,
                'message' => 'The face removal request is invalid.',
            ], 400);
        }

        $user->setFaceData(null);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Face ID removed.',
        ]);
    }

    private function denyFaceAccess(Users $user): void
    {
        $currentUser = $this->getUser();
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (!$currentUser instanceof Users || $currentUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
