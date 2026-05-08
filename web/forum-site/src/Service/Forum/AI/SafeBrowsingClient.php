<?php

namespace App\Service\Forum\AI;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class SafeBrowsingClient
{
    private const LINK_PATTERN = '/(https?:\/\/\S+|www\.\S+)/i';

    private string $apiKey;
    private int $timeoutMs;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $apiKey = null,
        ?int $timeoutMs = null,
    ) {
        $this->apiKey = trim($apiKey ?? (string) ($_ENV['SAFE_BROWSING_API_KEY'] ?? $_SERVER['SAFE_BROWSING_API_KEY'] ?? ''));
        $configuredTimeout = $timeoutMs ?? (int) ($_ENV['SAFE_BROWSING_TIMEOUT_MS'] ?? $_SERVER['SAFE_BROWSING_TIMEOUT_MS'] ?? 8000);
        $this->timeoutMs = $configuredTimeout > 0 ? $configuredTimeout : 8000;
    }

    public function scanTextDetailed(string ...$parts): SafeBrowsingScanResult
    {
        $urls = [];
        foreach ($parts as $part) {
            preg_match_all(self::LINK_PATTERN, $part, $matches);
            foreach ($matches[0] as $url) {
                $urls[] = trim((string) $url);
            }
        }

        $urls = array_values(array_unique(array_filter($urls)));
        if ($urls === []) {
            return new SafeBrowsingScanResult('NONE', 'NONE');
        }
        if ($this->apiKey === '') {
            return new SafeBrowsingScanResult('ERROR', 'ERROR');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HirelyForum/1.0 (Symfony)',
                ],
                'timeout' => max(1, $this->timeoutMs / 1000),
                'json' => [
                    'client' => [
                        'clientId' => 'hirely-forum',
                        'clientVersion' => '1.0.0',
                    ],
                    'threatInfo' => [
                        'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
                        'platformTypes' => ['ANY_PLATFORM'],
                        'threatEntryTypes' => ['URL'],
                        'threatEntries' => array_map(
                            static fn (string $url): array => ['url' => $url],
                            array_slice($urls, 0, 500)
                        ),
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $raw = $response->getContent(false);
            if ($statusCode < 200 || $statusCode >= 300) {
                return new SafeBrowsingScanResult('ERROR', 'ERROR');
            }

            /** @var array<string, mixed> $payload */
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $matches = $payload['matches'] ?? null;
            if (!is_array($matches) || $matches === []) {
                return new SafeBrowsingScanResult('NONE', 'NONE');
            }

            $types = [];
            foreach ($matches as $match) {
                if (!is_array($match)) {
                    continue;
                }
                $type = strtoupper(trim((string) ($match['threatType'] ?? '')));
                if ($type !== '') {
                    $types[] = $type;
                }
            }

            $types = array_values(array_unique($types));

            return new SafeBrowsingScanResult('FLAGGED', $types !== [] ? implode(',', $types) : 'FLAGGED');
        } catch (ExceptionInterface|\JsonException) {
            try {
                if (PHP_OS_FAMILY === 'Windows') {
                    return $this->scanWithCliFallback($urls);
                }
            } catch (\Throwable) {
            }

            return new SafeBrowsingScanResult('ERROR', 'ERROR');
        }
    }

    /**
     * @param list<string> $urls
     */
    private function scanWithCliFallback(array $urls): SafeBrowsingScanResult
    {
        $response = CliCurlFallback::postJson(
            'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=' . $this->apiKey,
            [
                'client' => [
                    'clientId' => 'hirely-forum',
                    'clientVersion' => '1.0.0',
                ],
                'threatInfo' => [
                    'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'UNWANTED_SOFTWARE'],
                    'platformTypes' => ['ANY_PLATFORM'],
                    'threatEntryTypes' => ['URL'],
                    'threatEntries' => array_map(
                        static fn (string $url): array => ['url' => $url],
                        array_slice($urls, 0, 500)
                    ),
                ],
            ],
            max(1, (int) ceil($this->timeoutMs / 1000))
        );

        if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
            return new SafeBrowsingScanResult('ERROR', 'ERROR');
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        $matches = $payload['matches'] ?? null;
        if (!is_array($matches) || $matches === []) {
            return new SafeBrowsingScanResult('NONE', 'NONE');
        }

        $types = [];
        foreach ($matches as $match) {
            if (!is_array($match)) {
                continue;
            }
            $type = strtoupper(trim((string) ($match['threatType'] ?? '')));
            if ($type !== '') {
                $types[] = $type;
            }
        }

        $types = array_values(array_unique($types));

        return new SafeBrowsingScanResult('FLAGGED', $types !== [] ? implode(',', $types) : 'FLAGGED');
    }
}
