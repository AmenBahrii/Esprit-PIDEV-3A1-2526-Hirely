<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserRoleSummaryService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
        private readonly string $model = 'meta-llama/llama-4-scout-17b-16e-instruct',
    ) {
    }

    /**
     * @param array<int, array{status:string, roleName:string, total:int}> $groupedStats
     */
    public function generateSummary(
        array $groupedStats,
        int $totalUsers,
        int $googleLinkedUsers,
        int $faceEnabledUsers
    ): string {
        if ($totalUsers === 0) {
            return 'Your platform does not have any user accounts yet.';
        }

        $statsLines = array_map(
            static fn (array $row): string => sprintf(
                '- %d %s %s account%s',
                $row['total'],
                strtolower($row['status']),
                strtolower($row['roleName']),
                $row['total'] === 1 ? '' : 's'
            ),
            $groupedStats
        );

        $prompt = <<<PROMPT
You are writing a short admin dashboard summary for platform users.

Write 2 or 3 plain-English sentences.

Rules:
- Mention the total number of users.
- Mention the most notable role/status patterns from the grouped data.
- Mention Google-linked accounts when relevant.
- Mention Face ID usage when relevant.
- Keep it helpful and operational.
- Do not use bullet points.
- Do not invent anything not present in the stats.

Stats:
Total users: {$totalUsers}
Google-linked users: {$googleLinkedUsers}
Face ID enabled users: {$faceEnabledUsers}
Grouped users:
PROMPT;

        $prompt .= "\n".implode("\n", $statsLines);

        if (trim($this->groqApiKey) === '') {
            return $this->buildFallbackSummary($groupedStats, $totalUsers, $googleLinkedUsers, $faceEnabledUsers);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->groqApiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You produce concise admin summaries for user and role activity.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'temperature' => 0.4,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(false);
        } catch (\Throwable) {
            return $this->buildFallbackSummary($groupedStats, $totalUsers, $googleLinkedUsers, $faceEnabledUsers);
        }

        if ($statusCode >= 400) {
            return $this->buildFallbackSummary($groupedStats, $totalUsers, $googleLinkedUsers, $faceEnabledUsers);
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || trim($content) === '') {
            return $this->buildFallbackSummary($groupedStats, $totalUsers, $googleLinkedUsers, $faceEnabledUsers);
        }

        return trim($content);
    }

    /**
     * @param array<int, array{status:string, roleName:string, total:int}> $groupedStats
     */
    private function buildFallbackSummary(
        array $groupedStats,
        int $totalUsers,
        int $googleLinkedUsers,
        int $faceEnabledUsers
    ): string {
        $parts = [sprintf('Your platform currently has %d user account%s.', $totalUsers, $totalUsers === 1 ? '' : 's')];

        if ($groupedStats !== []) {
            $topGroups = array_slice($groupedStats, 0, 2);
            $groupText = array_map(
                static fn (array $row): string => sprintf('%d %s %s', $row['total'], strtolower($row['status']), strtolower($row['roleName'])),
                $topGroups
            );
            $parts[] = 'The largest groups are '.implode(' and ', $groupText).'.';
        }

        if ($googleLinkedUsers > 0) {
            $parts[] = sprintf(
                '%d account%s use Google sign-in.',
                $googleLinkedUsers,
                $googleLinkedUsers === 1 ? '' : 's'
            );
        }

        if ($faceEnabledUsers > 0) {
            $parts[] = sprintf(
                '%d account%s already have Face ID enabled.',
                $faceEnabledUsers,
                $faceEnabledUsers === 1 ? '' : 's'
            );
        }

        return implode(' ', $parts);
    }
}
