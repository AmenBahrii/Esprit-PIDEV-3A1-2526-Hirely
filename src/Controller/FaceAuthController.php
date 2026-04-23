<?php

namespace App\Controller;

use App\Entity\User;
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

        /** @var User|null $user */
        $user = $entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
        ]);

        if (!$user instanceof User || strtolower((string) $user->getStatus()) !== 'active') {
            return $this->json([
                'success' => false,
                'message' => 'No active user was found for that email.',
            ], 404);
        }

        if ($faceRecognitionService->deserializeDescriptor($user->getFaceData()) === null) {
            return $this->json([
                'success' => false,
                'message' => 'You need to set up Face ID first.',
            ], 409);
        }

        if (!$faceRecognitionService->matchesStoredDescriptor($user->getFaceData(), $descriptor)) {
            return $this->json([
                'success' => false,
                'message' => 'Face verification failed. Try again in better lighting and face the camera directly.',
            ], 401);
        }

        $security->login($user, AppCustomAuthenticator::class, 'main');

        return $this->json([
            'success' => true,
            'redirect' => $this->generateUrl(in_array('ROLE_ADMIN', $user->getRoles(), true) ? 'app_admin' : 'app_workspace'),
        ]);
    }

    #[Route('/users/{id}/enroll', name: 'app_face_auth_enroll', methods: ['POST'])]
    public function enroll(
        Request $request,
        User $user,
        EntityManagerInterface $entityManager,
        FaceRecognitionService $faceRecognitionService
    ): JsonResponse {
        $this->denyFaceAccess($user);

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || !$this->isCsrfTokenValid('face_enroll_' . $user->getId(), (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'success' => false,
                'message' => 'The Face ID enrollment request is invalid.',
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
                'message' => 'Unable to save the captured Face ID data.',
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
        User $user,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyFaceAccess($user);

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload) || !$this->isCsrfTokenValid('face_remove_' . $user->getId(), (string) ($payload['_token'] ?? ''))) {
            return $this->json([
                'success' => false,
                'message' => 'The Face ID removal request is invalid.',
            ], 400);
        }

        $user->setFaceData(null);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Face ID removed.',
        ]);
    }

    private function denyFaceAccess(User $user): void
    {
        $currentUser = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        if (!$currentUser instanceof User || $currentUser->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
