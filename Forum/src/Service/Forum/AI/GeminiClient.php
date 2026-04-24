<?php

namespace App\Service\Forum\AI;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeminiClient
{
    private const MODEL_CANDIDATES = [
        'gemini-2.5-pro',
        'gemini-2.5-flash',
        'gemini-2.0-flash',
        'gemini-1.5-pro',
        'gemini-1.5-flash-8b',
    ];

    private const FALLBACK_TEXT = 'Gemini is unavailable right now (missing key or service error). Please try again later.';
    private const EMPTY_REPLY_FALLBACK = 'I\'m here, could you share a bit more detail about the role, company, or location?';
    private const RATE_LIMIT_FALLBACK = 'Gemini quota/rate limit reached right now. Please try again in a moment.';
    private const AUTH_FALLBACK = 'Gemini API key is invalid or not authorized for this project.';
    private const MODEL_FALLBACK = 'Gemini model endpoint is unavailable. Please update model config.';
    private const OUTPUT_MAX = 1200;
    private const CONTEXT_HEADER = "You are \"Gemini\", a bot that replies as a comment in a career-focused Hirely forum thread.\nWrite as a concise, professional forum commenter.\n- Use 3-8 short bullet points max, or one short paragraph plus bullets.\n- Stay job, internship, and career focused.\n- If the request is off-topic, steer it back to career context.\n- Do not mention prompts, APIs, moderation, or internal tooling.\n- Do not claim you took actions; only give guidance.";

    private string $apiKey;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        ?string $apiKey = null,
    ) {
        $this->apiKey = trim($apiKey ?? (string) ($_ENV['GEMINI_API_KEY'] ?? $_SERVER['GEMINI_API_KEY'] ?? ''));
    }

    public function generateReply(string $postTitle, string $postContent, string $userComment, ?string $tag): string
    {
        if ($this->apiKey === '' || strcasecmp($this->apiKey, 'YOUR_KEY_HERE') === 0) {
            return self::FALLBACK_TEXT;
        }

        $prompt = $this->buildPrompt($postTitle, $postContent, $userComment, $tag);
        $sawQuota = false;
        $sawMissingModel = false;

        foreach (self::MODEL_CANDIDATES as $model) {
            try {
                $response = $this->httpClient->request('POST', sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', $model, $this->apiKey), [
                    'headers' => ['Content-Type' => 'application/json'],
                    'timeout' => 35,
                    'json' => $this->buildPayload($prompt),
                ]);

                $statusCode = $response->getStatusCode();
                $raw = $response->getContent(false);
                if ($statusCode === 200) {
                    /** @var array<string, mixed> $payload */
                    $payload = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                    $text = $this->extractText($payload);

                    return $this->shapeOutput($text);
                }
                if ($statusCode === 429) {
                    $sawQuota = true;
                    continue;
                }
                if ($statusCode === 404) {
                    $sawMissingModel = true;
                    continue;
                }
                if (in_array($statusCode, [401, 403], true)) {
                    return self::AUTH_FALLBACK;
                }
            } catch (ExceptionInterface|\JsonException) {
                if (PHP_OS_FAMILY === 'Windows') {
                    try {
                        $fallback = CliCurlFallback::postJson(
                            sprintf('https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', $model, $this->apiKey),
                            $this->buildPayload($prompt),
                            35
                        );

                        if ($fallback['status_code'] === 200) {
                            /** @var array<string, mixed> $payload */
                            $payload = json_decode($fallback['body'], true, 512, JSON_THROW_ON_ERROR);

                            return $this->shapeOutput($this->extractText($payload));
                        }
                        if ($fallback['status_code'] === 429) {
                            $sawQuota = true;
                            continue;
                        }
                        if ($fallback['status_code'] === 404) {
                            $sawMissingModel = true;
                            continue;
                        }
                        if (in_array($fallback['status_code'], [401, 403], true)) {
                            return self::AUTH_FALLBACK;
                        }
                    } catch (\Throwable) {
                    }
                }

                continue;
            }
        }

        if ($sawQuota) {
            return self::RATE_LIMIT_FALLBACK;
        }
        if ($sawMissingModel) {
            return self::MODEL_FALLBACK;
        }

        return self::FALLBACK_TEXT;
    }

    public static function cleanTriggerToken(string $userComment): string
    {
        $cleaned = preg_replace('/@gemini/i', '', $userComment) ?? '';
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? '';

        return trim($cleaned);
    }

    private function buildPrompt(string $postTitle, string $postContent, string $userComment, ?string $tag): string
    {
        return self::CONTEXT_HEADER . "\n\n"
            . 'Post title: ' . $this->limit($this->clean($postTitle), 180) . "\n"
            . 'Post tag: ' . $this->limit($this->clean((string) $tag), 120) . "\n"
            . 'Post content: ' . $this->limit($this->clean($postContent), 1200) . "\n"
            . 'User comment: ' . $this->limit(self::cleanTriggerToken($userComment), 700) . "\n\n"
            . 'Task: Reply as a helpful career assistant comment.';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractText(array $payload): string
    {
        $candidates = $payload['candidates'] ?? null;
        if (!is_array($candidates)) {
            return '';
        }

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }
            $parts = $candidate['content']['parts'] ?? null;
            if (!is_array($parts)) {
                continue;
            }
            $chunks = [];
            foreach ($parts as $part) {
                if (is_array($part) && is_string($part['text'] ?? null) && trim($part['text']) !== '') {
                    $chunks[] = trim($part['text']);
                }
            }
            if ($chunks !== []) {
                return implode("\n", $chunks);
            }
        }

        return '';
    }

    private function shapeOutput(string $raw): string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return self::EMPTY_REPLY_FALLBACK;
        }

        if (strlen($trimmed) > 600 && !str_contains($trimmed, "\n")) {
            $sentences = preg_split('/(?<=[.!?])\s+/', $trimmed) ?: [];
            if (count($sentences) >= 3) {
                $trimmed = implode("\n", array_map(
                    static fn (string $sentence): string => '- ' . trim($sentence),
                    array_filter($sentences, static fn (string $sentence): bool => trim($sentence) !== '')
                ));
            }
        }

        $trimmed = $this->limit($trimmed, self::OUTPUT_MAX);

        return $trimmed !== '' ? $trimmed : self::EMPTY_REPLY_FALLBACK;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(string $prompt): array
    {
        return [
            'contents' => [[
                'parts' => [[
                    'text' => $prompt,
                ]],
            ]],
        ];
    }

    private function clean(string $value): string
    {
        return trim(str_replace("\r", ' ', $value));
    }

    private function limit(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }
        $slice = substr($value, 0, $max);
        $lastWhitespace = strrpos($slice, ' ');
        if ($lastWhitespace !== false && $lastWhitespace > (int) ($max / 2)) {
            $slice = substr($slice, 0, $lastWhitespace);
        }

        return rtrim($slice) . '...';
    }
}


