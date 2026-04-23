<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly ?string $redirectUri = '',
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->clientId) !== '' && trim($this->clientSecret) !== '';
    }

    public function buildAuthorizationUrl(string $redirectUri, string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ]);

        return self::AUTH_URL . '?' . $query;
    }

    /**
     * @return array{sub:string,email:string,given_name:?string,family_name:?string,name:?string,email_verified?:bool}
     */
    public function fetchUserProfile(string $code, string $redirectUri): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Google sign-in is not configured yet.');
        }

        try {
            $tokenResponse = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'code' => $code,
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ])->toArray(false);
        } catch (\Throwable) {
            throw new \RuntimeException('Google sign-in could not exchange the authorization code.');
        }

        $accessToken = $tokenResponse['access_token'] ?? null;
        if (!is_string($accessToken) || $accessToken === '') {
            throw new \RuntimeException('Google sign-in did not return a usable access token.');
        }

        try {
            $profile = $this->httpClient->request('GET', self::USERINFO_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ],
            ])->toArray(false);
        } catch (\Throwable) {
            throw new \RuntimeException('Google sign-in could not load the user profile.');
        }

        $sub = $profile['sub'] ?? null;
        $email = $profile['email'] ?? null;

        if (!is_string($sub) || $sub === '' || !is_string($email) || $email === '') {
            throw new \RuntimeException('Google sign-in returned an incomplete profile.');
        }

        if (($profile['email_verified'] ?? false) !== true) {
            throw new \RuntimeException('Google account email must be verified before signing in.');
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'given_name' => isset($profile['given_name']) && is_string($profile['given_name']) ? $profile['given_name'] : null,
            'family_name' => isset($profile['family_name']) && is_string($profile['family_name']) ? $profile['family_name'] : null,
            'name' => isset($profile['name']) && is_string($profile['name']) ? $profile['name'] : null,
            'picture' => isset($profile['picture']) && is_string($profile['picture']) ? $profile['picture'] : null,
            'email_verified' => (bool) ($profile['email_verified'] ?? false),
        ];
    }

    public function getRedirectUri(?string $fallback = null): string
    {
        $configured = trim((string) $this->redirectUri);

        if ($configured !== '') {
            return $configured;
        }

        if ($fallback === null || trim($fallback) === '') {
            throw new \RuntimeException('Google redirect URI is not configured.');
        }

        return $fallback;
    }

    public function resolveRedirectUri(string $fallback): string
    {
        return $this->getRedirectUri($fallback);
    }

    public function getAuthorizationUrl(string $state, string $redirectUri): string
    {
        return $this->buildAuthorizationUrl($redirectUri, $state);
    }
}
