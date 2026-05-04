<?php

namespace App\Tests\Onboarding;

use App\Onboarding\LibreTranslateService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LibreTranslateServiceTest extends TestCase
{
    public function testDisabledServiceReturnsNormalizedOriginalTextsWithoutCallingApi(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            self::fail('The translation API must not be called when no API URL is configured.');
        });
        $service = new LibreTranslateService($client, new NullLogger(), '');

        $translated = $service->translateMap([
            'title' => '  Start onboarding  ',
            'empty' => null,
        ], 'fr');

        self::assertFalse($service->isEnabled());
        self::assertSame([
            'title' => 'Start onboarding',
            'empty' => '',
        ], $translated);
    }

    public function testLanguageCodesAreNormalizedToSupportedValues(): void
    {
        $service = new LibreTranslateService(new MockHttpClient(), new NullLogger(), '');

        self::assertSame('fr', $service->normalizeLanguage('fr'));
        self::assertSame(LibreTranslateService::DEFAULT_LANGUAGE, $service->normalizeLanguage('pirate'));
        self::assertSame(LibreTranslateService::DEFAULT_LANGUAGE, $service->normalizeLanguage(null));
    }

    public function testEnabledServicePostsUniqueTextsToLibreTranslateEndpoint(): void
    {
        $requests = [];
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [
                'method' => $method,
                'url' => $url,
                'body' => (string) ($options['body'] ?? ''),
            ];

            return new MockResponse(json_encode(['translatedText' => 'Bonjour']) ?: '{}', [
                'http_code' => 200,
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        });
        $service = new LibreTranslateService($client, new NullLogger(), 'https://translate.example/api', 'demo-key');

        $translated = $service->translateMap([
            'one' => 'Hello',
            'two' => 'Hello',
        ], 'fr');

        self::assertTrue($service->isEnabled());
        self::assertSame(['one' => 'Bonjour', 'two' => 'Bonjour'], $translated);
        self::assertCount(1, $requests);
        self::assertSame('POST', $requests[0]['method']);
        self::assertSame('https://translate.example/api/translate', $requests[0]['url']);
        self::assertStringContainsString('"q":"Hello"', $requests[0]['body']);
        self::assertStringContainsString('"target":"fr"', $requests[0]['body']);
        self::assertStringContainsString('"api_key":"demo-key"', $requests[0]['body']);
    }
}
