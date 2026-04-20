<?php

namespace App\Onboarding;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class OnboardingLanguageContext
{
    private const SESSION_KEY = 'onboarding.language';

    /**
     * @var array<string, array<string, string>>
     */
    private array $translationCache = [];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly LibreTranslateService $libreTranslateService,
    ) {
    }

    public function resolveFromRequest(?Request $request = null): string
    {
        $request ??= $this->requestStack->getCurrentRequest();

        if (!$request) {
            return LibreTranslateService::DEFAULT_LANGUAGE;
        }

        if ($request->attributes->has('_onboarding_language')) {
            return (string) $request->attributes->get('_onboarding_language');
        }

        $language = $request->query->has('lang')
            ? $this->libreTranslateService->normalizeLanguage((string) $request->query->get('lang'))
            : $this->getStoredLanguage($request);

        $request->attributes->set('_onboarding_language', $language);

        if ($request->hasSession()) {
            $request->getSession()->set(self::SESSION_KEY, $language);
        }

        return $language;
    }

    public function getCurrentLanguage(): string
    {
        return $this->resolveFromRequest();
    }

    /**
     * @return array<string, string>
     */
    public function getLanguageChoices(): array
    {
        return $this->libreTranslateService->getLanguageChoices();
    }

    public function isEnabled(): bool
    {
        return $this->libreTranslateService->isEnabled();
    }

    /**
     * @param array<string, string|null> $texts
     * @return array<string, string>
     */
    public function translateMap(array $texts, ?string $language = null): array
    {
        $language ??= $this->getCurrentLanguage();
        $cacheKey = $language . ':' . md5(serialize($texts));

        if (!isset($this->translationCache[$cacheKey])) {
            $this->translationCache[$cacheKey] = $this->libreTranslateService->translateMap($texts, $language);
        }

        return $this->translationCache[$cacheKey];
    }

    private function getStoredLanguage(Request $request): string
    {
        if ($request->hasSession()) {
            return $this->libreTranslateService->normalizeLanguage(
                (string) $request->getSession()->get(self::SESSION_KEY, LibreTranslateService::DEFAULT_LANGUAGE)
            );
        }

        return LibreTranslateService::DEFAULT_LANGUAGE;
    }
}
