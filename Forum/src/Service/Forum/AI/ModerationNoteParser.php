<?php

namespace App\Service\Forum\AI;

final class ModerationNoteParser
{
    /**
     * @return array{
     *   duplicate:?string,
     *   toxicity:?string,
     *   relevance:?string,
     *   quality:?string,
     *   link_threat:?string,
     *   category:string,
     *   decision:?string,
     *   fallback:string
     * }
     */
    public function parse(?string $note): array
    {
        $text = trim((string) $note);
        $parsed = [
            'duplicate' => $this->matchMetric($text, 'Duplicate'),
            'toxicity' => $this->matchMetric($text, 'Toxicity'),
            'relevance' => $this->matchMetric($text, 'Relevance'),
            'quality' => $this->matchMetric($text, 'Quality'),
            'link_threat' => $this->matchMetric($text, 'LinkThreat'),
            'category' => $this->matchCategory($text) ?? 'General',
            'decision' => $this->matchMetric($text, 'Decision'),
            'fallback' => preg_match('/Fallback\s*=\s*YES/i', $text) === 1 ? 'Yes' : 'No',
        ];

        if ($parsed['decision'] === null && preg_match('/\b(APPROVED|PENDING|REJECTED)\b/i', $text, $match) === 1) {
            $parsed['decision'] = strtoupper($match[1]);
        }

        return $parsed;
    }

    private function matchMetric(string $text, string $metric): ?string
    {
        if (preg_match('/' . preg_quote($metric, '/') . '\s*=\s*([^|]+)/i', $text, $match) !== 1) {
            return null;
        }

        return trim($match[1]);
    }

    private function matchCategory(string $text): ?string
    {
        if (preg_match('/\(([^()]+)\)\s*(?:\||$)/', $text, $match) !== 1) {
            return null;
        }

        return trim($match[1]);
    }
}


