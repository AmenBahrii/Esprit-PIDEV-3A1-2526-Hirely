<?php

namespace App\Service\Forum\AI;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class PythonAiClient
{
    private string $baseUrl;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $baseUrl = null,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? $this->readEnv('PY_AI_URL', 'http://127.0.0.1:8008'), '/');
    }

    /**
     * @throws \RuntimeException
     */
    public function score(string $text, string $type, string $contentKey = ''): PythonScoreResult
    {
        try {
            $response = $this->httpClient->request('POST', $this->baseUrl . '/score', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'HirelyForum/1.0 (Symfony)',
                ],
                'timeout' => 6,
                'json' => [
                    'text' => $text,
                    'type' => strtolower($type) === 'comment' ? 'comment' : 'post',
                    'content_key' => $contentKey,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $raw = $response->getContent(false);
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new \RuntimeException(sprintf(
                    'Python score request failed (%d): %s',
                    $statusCode,
                    $this->preview($raw)
                ));
            }

            /** @var array<string, mixed> $payload */
            $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            /** @var array<string, mixed> $reasons */
            $reasons = is_array($payload['reasons'] ?? null) ? $payload['reasons'] : [];

            return new PythonScoreResult(
                $this->readFloat($payload, 'relevance', 0.5),
                $this->readString($payload, ['predictedCategory', 'predicted_category', 'category'], 'General'),
                $this->readFloat($payload, 'quality', 0.5),
                $this->readFloat($payload, 'duplicate_score', $this->readFloat($payload, 'duplicate_similarity', 0.0)),
                $this->readOptionalInt($payload, ['duplicate_of_post_id', 'duplicateOfPostId']),
                $this->readStringList($reasons, 'relevance'),
                $this->readStringList($reasons, 'quality'),
                $this->readStringList($reasons, 'duplicate'),
                $this->trimRaw($raw),
                $response->getInfo('total_time') !== null ? (int) round(((float) $response->getInfo('total_time')) * 1000) : 0,
            );
        } catch (ExceptionInterface|\JsonException $exception) {
            throw new \RuntimeException($this->safeMessage($exception), 0, $exception);
        }
    }

    private function readEnv(string $name, string $default): string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? $default;

        return trim((string) $value) !== '' ? trim((string) $value) : $default;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readFloat(array $payload, string $field, float $fallback): float
    {
        $value = $payload[$field] ?? null;
        if (!is_numeric($value)) {
            return $fallback;
        }

        return (float) $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $fields
     */
    private function readString(array $payload, array $fields, string $fallback): string
    {
        foreach ($fields as $field) {
            $value = $payload[$field] ?? null;
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $fallback;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $fields
     */
    private function readOptionalInt(array $payload, array $fields): ?int
    {
        foreach ($fields as $field) {
            $value = $payload[$field] ?? null;
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function readStringList(array $payload, string $field): array
    {
        $values = $payload[$field] ?? null;
        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $values
        )));
    }

    private function trimRaw(string $raw): string
    {
        $compact = preg_replace('/\s+/', ' ', $raw) ?? '';

        return strlen($compact) <= 2000 ? $compact : substr($compact, 0, 2000) . '...';
    }

    private function preview(string $raw): string
    {
        $compact = preg_replace('/\s+/', ' ', $raw) ?? '';

        return strlen($compact) <= 220 ? $compact : substr($compact, 0, 220) . '...';
    }

    private function safeMessage(\Throwable $exception): string
    {
        $message = trim(str_replace(["\r", "\n"], ' ', $exception->getMessage()));

        return $message !== '' ? $message : $exception::class;
    }
}


