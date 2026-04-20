<?php

namespace App\Onboarding;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class LibreTranslateService
{
    public const DEFAULT_LANGUAGE = 'en';

    private const LANGUAGE_CHOICES = [
        'English' => 'en',
        'French' => 'fr',
        'Arabic' => 'ar',
        'Spanish' => 'es',
        'German' => 'de',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiUrl,
        private readonly ?string $apiKey = null,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function getLanguageChoices(): array
    {
        return self::LANGUAGE_CHOICES;
    }

    public function normalizeLanguage(?string $language): string
    {
        $language = trim((string) $language);

        return \in_array($language, array_values(self::LANGUAGE_CHOICES), true)
            ? $language
            : self::DEFAULT_LANGUAGE;
    }

    public function isEnabled(): bool
    {
        return '' !== trim($this->apiUrl);
    }

    /**
     * @param array<string, string|null> $texts
     * @return array<string, string>
     */
    public function translateMap(array $texts, string $targetLanguage, string $sourceLanguage = self::DEFAULT_LANGUAGE): array
    {
        $targetLanguage = $this->normalizeLanguage($targetLanguage);
        $sourceLanguage = $this->normalizeLanguage($sourceLanguage);

        $normalized = [];
        foreach ($texts as $key => $text) {
            $normalized[$key] = trim((string) $text);
        }

        if (!$this->isEnabled() || $targetLanguage === $sourceLanguage) {
            return $normalized;
        }

        $translatedLookup = [];
        foreach (array_unique(array_values($normalized)) as $text) {
            if ('' === $text) {
                $translatedLookup[$text] = $text;
                continue;
            }

            $translatedLookup[$text] = $this->translateText($text, $targetLanguage, $sourceLanguage);
        }

        $translated = [];
        foreach ($normalized as $key => $text) {
            $translated[$key] = $translatedLookup[$text] ?? $text;
        }

        return $translated;
    }

    private function translateText(string $text, string $targetLanguage, string $sourceLanguage): string
    {
        $payload = [
            'q' => $text,
            'source' => $sourceLanguage,
            'target' => $targetLanguage,
            'format' => 'text',
        ];

        $apiKey = trim((string) $this->apiKey);
        if ('' !== $apiKey) {
            $payload['api_key'] = $apiKey;
        }

        try {
            $response = $this->httpClient->request('POST', rtrim($this->apiUrl, '/') . '/translate', [
                'json' => $payload,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'timeout' => 12,
            ]);

            $data = $response->toArray(false);
            if (isset($data['translatedText']) && \is_string($data['translatedText']) && '' !== trim($data['translatedText'])) {
                return trim($data['translatedText']);
            }

            if (isset($data['error'])) {
                $this->logger->warning('LibreTranslate returned an error response.', [
                    'error' => $data['error'],
                    'target_language' => $targetLanguage,
                ]);
            }
        } catch (\Throwable $exception) {
            $this->logger->warning('LibreTranslate request failed.', [
                'message' => $exception->getMessage(),
                'target_language' => $targetLanguage,
            ]);
        }

        return $text;
    }
}
