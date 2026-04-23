<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\User;
use App\Security\AppCustomAuthenticator;
use App\Service\GoogleOAuthService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GoogleAuthController extends AbstractController
{
    private const GOOGLE_STATE_KEY = 'hirely_google_oauth_state';
    private const GOOGLE_INTENT_KEY = 'hirely_google_oauth_intent';
    private const GOOGLE_PROFILE_KEY = 'hirely_google_registration_profile';

    #[Route('/connect/google', name: 'app_google_connect', methods: ['GET'])]
    public function connect(Request $request, GoogleOAuthService $googleOAuthService): Response
    {
        return $this->startGoogleFlow($request, $googleOAuthService, 'login');
    }

    #[Route('/connect/google/link', name: 'app_google_connect_link', methods: ['GET'])]
    public function link(Request $request, GoogleOAuthService $googleOAuthService): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        return $this->startGoogleFlow($request, $googleOAuthService, 'link');
    }

    #[Route('/connect/google/check', name: 'app_google_check', methods: ['GET'])]
    #[Route('/connect/google/callback', name: 'app_google_callback', methods: ['GET'])]
    public function callback(
        Request $request,
        GoogleOAuthService $googleOAuthService,
        EntityManagerInterface $entityManager,
        Security $security,
    ): Response {
        if (!$googleOAuthService->isConfigured()) {
            $this->addFlash('error', 'Google sign-in is not configured yet.');

            return $this->redirectToRoute('app_login');
        }

        $session = $request->getSession();
        $expectedState = (string) $session->get(self::GOOGLE_STATE_KEY, '');
        $intent = (string) $session->get(self::GOOGLE_INTENT_KEY, 'login');
        $session->remove(self::GOOGLE_STATE_KEY);
        $session->remove(self::GOOGLE_INTENT_KEY);

        $receivedState = (string) $request->query->get('state', '');
        $code = (string) $request->query->get('code', '');

        if ($expectedState === '' || $receivedState === '' || !hash_equals($expectedState, $receivedState)) {
            $this->addFlash('error', 'Google sign-in session expired. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        if ($code === '') {
            $this->addFlash('error', 'Google sign-in was cancelled or did not return a code.');

            return $this->redirectToRoute('app_login');
        }

        try {
            $profile = $googleOAuthService->fetchUserProfile(
                $code,
                $googleOAuthService->resolveRedirectUri(
                    $this->generateUrl('app_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
                )
            );
        } catch (\Throwable $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_login');
        }

        $userRepository = $entityManager->getRepository(User::class);
        /** @var User|null $googleLinkedUser */
        $googleLinkedUser = $userRepository->findOneBy(['google_id' => $profile['sub']]);

        if ($intent === 'link') {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof User) {
                $this->addFlash('error', 'You need to be signed in before linking Google.');

                return $this->redirectToRoute('app_login');
            }

            if ($googleLinkedUser instanceof User && $googleLinkedUser->getId() !== $currentUser->getId()) {
                $this->addFlash('error', 'That Google account is already linked to another user.');

                return $this->redirectToRoute('app_users_edit', ['id' => $currentUser->getId()]);
            }

            $currentUser->setGoogleId($profile['sub']);
            if (!$currentUser->getProfilePic() && !empty($profile['picture'])) {
                $currentUser->setProfilePic((string) $profile['picture']);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Google account linked successfully.');

            return $this->redirectToRoute('app_users_edit', ['id' => $currentUser->getId()]);
        }

        /** @var User|null $user */
        $user = $googleLinkedUser;
        if (!$user) {
            $user = $userRepository->findOneBy(['email' => $profile['email']]);
        }

        if ($user instanceof User) {
            if (strtolower((string) $user->getStatus()) !== 'active') {
                $this->addFlash('error', 'This account is inactive. Contact an administrator for help.');

                return $this->redirectToRoute('app_login');
            }

            if (!$user->getGoogleId()) {
                $user->setGoogleId($profile['sub']);
            }

            if (!$user->getProfilePic() && !empty($profile['picture'])) {
                $user->setProfilePic((string) $profile['picture']);
            }

            if (!$user->getFirstName() && !empty($profile['given_name'])) {
                $user->setFirstName((string) $profile['given_name']);
            }

            if (!$user->getLastName() && !empty($profile['family_name'])) {
                $user->setLastName((string) $profile['family_name']);
            }

            $entityManager->flush();

            return $security->login($user, AppCustomAuthenticator::class, 'main')
                ?? $this->redirectToRoute($this->getHomeRouteForUser($user));
        }

        $session->set(self::GOOGLE_PROFILE_KEY, [
            'sub' => (string) $profile['sub'],
            'email' => (string) $profile['email'],
            'given_name' => (string) ($profile['given_name'] ?? ''),
            'family_name' => (string) ($profile['family_name'] ?? ''),
            'name' => (string) ($profile['name'] ?? ''),
            'picture' => isset($profile['picture']) ? (string) $profile['picture'] : null,
        ]);

        return $this->redirectToRoute('app_google_complete');
    }

    #[Route('/connect/google/complete', name: 'app_google_complete', methods: ['GET', 'POST'])]
    public function complete(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
    ): Response {
        $profile = $request->getSession()->get(self::GOOGLE_PROFILE_KEY);

        if (!is_array($profile)) {
            $this->addFlash('error', 'Please start Google sign-in again.');

            return $this->redirectToRoute('app_login');
        }

        if ($request->isMethod('POST')) {
            $selectedRole = strtolower($request->request->getString('role'));

            if (!in_array($selectedRole, ['candidate', 'recruiter'], true)) {
                $this->addFlash('error', 'Please choose a valid account type.');

                return $this->redirectToRoute('app_google_complete');
            }

            /** @var Role|null $role */
            $role = $entityManager->getRepository(Role::class)->createQueryBuilder('r')
                ->andWhere('LOWER(r.name) = :name')
                ->setParameter('name', $selectedRole)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if (!$role instanceof Role) {
                $this->addFlash('error', 'The selected role is not available right now.');

                return $this->redirectToRoute('app_login');
            }

            $user = new User();
            [$firstName, $lastName] = $this->extractNames($profile);

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail((string) $profile['email']);
            $user->setRole($role);
            $user->setStatus('active');
            $user->setGoogleId((string) $profile['sub']);
            $user->setProfilePic(isset($profile['picture']) ? (string) $profile['picture'] : null);
            $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));

            $entityManager->persist($user);
            $entityManager->flush();

            $request->getSession()->remove(self::GOOGLE_PROFILE_KEY);

            return $security->login($user, AppCustomAuthenticator::class, 'main')
                ?? $this->redirectToRoute($this->getHomeRouteForUser($user));
        }

        return $this->render('security/google_complete.html.twig', [
            'profile' => $profile,
        ]);
    }

    private function startGoogleFlow(Request $request, GoogleOAuthService $googleOAuthService, string $intent): RedirectResponse
    {
        if (!$googleOAuthService->isConfigured()) {
            $this->addFlash('error', 'Google sign-in is not configured yet. Add your Google client ID and secret first.');

            return $this->redirectToRoute('app_login');
        }

        $state = bin2hex(random_bytes(24));
        $request->getSession()->set(self::GOOGLE_STATE_KEY, $state);
        $request->getSession()->set(self::GOOGLE_INTENT_KEY, $intent);

        $redirectUri = $googleOAuthService->resolveRedirectUri(
            $this->generateUrl('app_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return $this->redirect($googleOAuthService->getAuthorizationUrl($state, $redirectUri));
    }

    /**
     * @param array<string, mixed> $profile
     * @return array{0: string, 1: string}
     */
    private function extractNames(array $profile): array
    {
        $firstName = trim((string) ($profile['given_name'] ?? ''));
        $lastName = trim((string) ($profile['family_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return [$firstName !== '' ? $firstName : 'Google', $lastName !== '' ? $lastName : 'User'];
        }

        $parts = preg_split('/\s+/', trim((string) ($profile['name'] ?? 'Google User'))) ?: [];
        $first = $parts[0] ?? 'Google';
        $last = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : 'User';

        return [$first, $last];
    }

    private function getHomeRouteForUser(User $user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'app_admin';
        }

        return 'app_workspace';
    }
}
