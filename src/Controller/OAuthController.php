<?php

namespace App\Controller;

use App\Entity\Role;
use App\Entity\Users;
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

final class OAuthController extends AbstractController
{
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

    #[Route('/connect/google/callback', name: 'app_google_callback', methods: ['GET'])]
    public function callback(
        Request $request,
        GoogleOAuthService $googleOAuthService,
        EntityManagerInterface $entityManager,
        Security $security
    ): Response {
        $session = $request->getSession();
        $expectedState = $session->get('google_oauth_state');
        $intent = $session->get('google_oauth_intent', 'login');

        if (!$googleOAuthService->isConfigured()) {
            $this->addFlash('error', 'Google sign-in is not configured yet.');

            return $this->redirectToRoute('app_login');
        }

        if (!$expectedState || $request->query->getString('state') !== $expectedState) {
            $this->addFlash('error', 'The Google sign-in session expired. Please try again.');

            return $this->redirectToRoute('app_login');
        }

        $session->remove('google_oauth_state');
        $session->remove('google_oauth_intent');

        $code = $request->query->getString('code');
        if ($code === '') {
            $this->addFlash('error', 'Google did not return an authorization code.');

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

        $googleId = (string) $profile['sub'];
        $email = strtolower((string) $profile['email']);
        $picture = isset($profile['picture']) ? (string) $profile['picture'] : null;

        $googleLinkedUser = $entityManager->getRepository(Users::class)->findOneBy([
            'google_id' => $googleId,
        ]);

        if ($intent === 'link') {
            $currentUser = $this->getUser();
            if (!$currentUser instanceof Users) {
                $this->addFlash('error', 'You need to be signed in before linking Google.');

                return $this->redirectToRoute('app_login');
            }

            if ($googleLinkedUser instanceof Users && $googleLinkedUser->getId() !== $currentUser->getId()) {
                $this->addFlash('error', 'That Google account is already linked to another user.');

                return $this->redirectToRoute('app_users_edit', ['id' => $currentUser->getId()]);
            }

            $currentUser->setGoogleId($googleId);
            if (!$currentUser->getProfilePic() && $picture) {
                $currentUser->setProfilePic($picture);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Google account linked successfully.');

            return $this->redirectToRoute('app_users_edit', ['id' => $currentUser->getId()]);
        }

        $user = $googleLinkedUser;

        if (!$user instanceof Users) {
            $user = $entityManager->getRepository(Users::class)->findOneBy([
                'email' => $email,
            ]);
        }

        if ($user instanceof Users) {
            if ($user->getStatus() !== 'active') {
                $this->addFlash('error', 'This account is inactive. Contact an administrator for help.');

                return $this->redirectToRoute('app_login');
            }

            if (!$user->getGoogleId()) {
                $user->setGoogleId($googleId);
            }

            if (!$user->getProfilePic() && $picture) {
                $user->setProfilePic($picture);
            }

            $entityManager->flush();
            $security->login($user, AppCustomAuthenticator::class, 'main');

            return $this->redirectToRoute($this->getHomeRouteForUser($user));
        }

        $session->set('google_registration_profile', [
            'sub' => $googleId,
            'email' => $email,
            'given_name' => (string) ($profile['given_name'] ?? ''),
            'family_name' => (string) ($profile['family_name'] ?? ''),
            'name' => (string) ($profile['name'] ?? ''),
            'picture' => $picture,
        ]);

        return $this->redirectToRoute('app_google_complete');
    }

    #[Route('/connect/google/complete', name: 'app_google_complete', methods: ['GET', 'POST'])]
    public function complete(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        Security $security
    ): Response {
        $profile = $request->getSession()->get('google_registration_profile');

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

            $role = $entityManager->getRepository(Role::class)->findOneBy([
                'name' => $selectedRole,
            ]);

            if (!$role instanceof Role) {
                $this->addFlash('error', 'The selected role is not available right now.');

                return $this->redirectToRoute('app_login');
            }

            $user = new Users();
            [$firstName, $lastName] = $this->extractNames($profile);

            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $user->setEmail((string) $profile['email']);
            $user->setRole($role);
            $user->setStatus('active');
            $user->setGoogleId((string) $profile['sub']);
            $user->setProfilePic($profile['picture'] ?? null);
            $user->setPassword(
                $passwordHasher->hashPassword($user, bin2hex(random_bytes(32)))
            );

            $entityManager->persist($user);
            $entityManager->flush();

            $request->getSession()->remove('google_registration_profile');
            $security->login($user, AppCustomAuthenticator::class, 'main');

            return $this->redirectToRoute($this->getHomeRouteForUser($user));
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
        $request->getSession()->set('google_oauth_state', $state);
        $request->getSession()->set('google_oauth_intent', $intent);

        return $this->redirect($googleOAuthService->getAuthorizationUrl(
            $state,
            $googleOAuthService->resolveRedirectUri(
                $this->generateUrl('app_google_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            )
        ));
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

    private function getHomeRouteForUser(Users $user): string
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return 'app_joboffer_index';
        }

        if (in_array('ROLE_RECRUITER', $roles, true)) {
            return 'app_joboffer_index';
        }

        return 'app_joboffer_index';
    }
}
