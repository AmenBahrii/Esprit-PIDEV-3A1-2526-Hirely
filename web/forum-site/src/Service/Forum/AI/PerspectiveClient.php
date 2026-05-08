<?php

namespace App\Service\Forum\AI;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PerspectiveClient
{
    private string $apiKey;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $apiKey = null,
    ) {
        $this->apiKey = trim($apiKey ?? (string) ($_ENV['PERSPECTIVE_API_KEY'] ?? $_SERVER['PERSPECTIVE_API_KEY'] ?? ''));
    }

    /**
     * @throws \RuntimeException
     */
    public function analyze(string $text): PerspectiveResult
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('Missing PERSPECTIVE_API_KEY');
        }
        if (strcasecmp($this->apiKey, 'YOUR_KEY_HERE') === 0) {
            throw new \RuntimeException('Replace YOUR_KEY_HERE');
        }

        try {
            $response = $this->httpClient->request('POST', 'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=' . $this->apiKey, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HirelyForum/1.0 (Symfony)',
                ],
                'timeout' => 5,
                'json' => [
                    'comment' => ['text' => $text],
                    'languages' => ['en'],
                    'requestedAttributes' => ['TOXICITY' => new \stdClass()],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $raw = $response->getContent(false);
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \RuntimeException(sprintf('Perspective request failed (%d)', $statusCode));
            }

            /** @var array<string, mixed> $payload */
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            $toxicity = $payload['attributeScores']['TOXICITY']['summaryScore']['value'] ?? null;
            if (!is_numeric($toxicity)) {
                throw new \RuntimeException('Perspective response missing TOXICITY summaryScore');
            }

            return new PerspectiveResult(
                max(0.0, min(1.0, (float) $toxicity)),
                $this->trimRaw($raw),
                $response->getInfo('total_time') !== null ? (int) round(((float) $response->getInfo('total_time')) * 1000) : 0,
            );
        } catch (ExceptionInterface|\JsonException $exception) {
            if ($this->shouldUseCliFallback($exception)) {
                return $this->analyzeWithCliFallback($text);
            }

            throw new \RuntimeException($this->safeMessage($exception), 0, $exception);
        }
    }

    private function analyzeWithCliFallback(string $text): PerspectiveResult
    {
        $startedAt = microtime(true);
        $response = CliCurlFallback::postJson(
            'https://commentanalyzer.googleapis.com/v1alpha1/comments:analyze?key=' . $this->apiKey,
            [
                'comment' => ['text' => $text],
                'languages' => ['en'],
                'requestedAttributes' => ['TOXICITY' => new \stdClass()],
            ],
            10
        );

        if ($response['status_code'] < 200 || $response['status_code'] >= 300) {
            throw new \RuntimeException(sprintf('Perspective request failed (%d)', $response['status_code']));
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        $toxicity = $payload['attributeScores']['TOXICITY']['summaryScore']['value'] ?? null;
        if (!is_numeric($toxicity)) {
            throw new \RuntimeException('Perspective response missing TOXICITY summaryScore');
        }

        return new PerspectiveResult(
            max(0.0, min(1.0, (float) $toxicity)),
            $this->trimRaw($response['body']),
            (int) round((microtime(true) - $startedAt) * 1000)
        );
    }

    private function shouldUseCliFallback(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return PHP_OS_FAMILY === 'Windows'
            && (
                str_contains($message, 'ssl certificate problem')
                || str_contains($message, 'crypt_e_no_revocation_check')
                || str_contains($message, 'schannel')
            );
    }

    private function trimRaw(string $raw): string
    {
        $compact = preg_replace('/\s+/', ' ', $raw) ?? '';

        return strlen($compact) <= 2000 ? $compact : substr($compact, 0, 2000) . '...';
    }

    private function safeMessage(\Throwable $exception): string
    {
        $message = trim(str_replace(["\r", "\n"], ' ', $exception->getMessage()));

        return $message !== '' ? $message : $exception::class;
    }
}
