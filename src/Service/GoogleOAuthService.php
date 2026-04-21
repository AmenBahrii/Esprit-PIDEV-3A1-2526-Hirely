<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleOAuthService
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $configuredRedirectUri,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function resolveRedirectUri(string $defaultRedirectUri): string
    {
        return $this->configuredRedirectUri !== '' ? $this->configuredRedirectUri : $defaultRedirectUri;
    }

    public function getAuthorizationUrl(string $state, string $redirectUri): string
    {
        return self::AUTH_URL . '?' . http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchUserProfile(string $code, string $redirectUri): array
    {
        $tokenData = $this->httpClient->request('POST', self::TOKEN_URL, [
            'body' => [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ],
        ])->toArray(false);

        if (!isset($tokenData['access_token'])) {
            throw new \RuntimeException('Google did not return an access token.');
        }

        $profile = $this->httpClient->request('GET', self::USERINFO_URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $tokenData['access_token'],
            ],
        ])->toArray(false);

        if (($profile['email_verified'] ?? false) !== true) {
            throw new \RuntimeException('Google account email must be verified before signing in.');
        }

        if (!isset($profile['sub'], $profile['email'])) {
            throw new \RuntimeException('Google profile response is missing required fields.');
        }

        return $profile;
    }
}
