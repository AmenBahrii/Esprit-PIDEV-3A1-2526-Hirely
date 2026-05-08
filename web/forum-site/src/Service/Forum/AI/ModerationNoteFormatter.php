<?php

namespace App\Service\Forum\AI;

final class ModerationNoteFormatter
{
    private const MAX_LENGTH = 500;

    public function buildSimpleAiNote(ModerationReport $report): string
    {
        $duplicate = $this->clamp01($report->getDuplicateScore());
        $toxicity = $this->clamp01($report->getToxicity());
        $relevance = $this->clamp01($report->getRelevance());
        $quality = $this->clamp01($report->getQualityScore());
        $category = trim($report->getPredictedCategory()) !== '' ? trim($report->getPredictedCategory()) : 'General';
        $linkThreat = $this->resolveThreatValue($report->getLinkThreatTypes(), $report->getLinkThreat());

        $note = sprintf(
            'AI: Duplicate=%.2f | Toxicity=%.2f | Relevance=%.2f | Quality=%.2f | LinkThreat=%s (%s)',
            $duplicate,
            $toxicity,
            $relevance,
            $quality,
            $linkThreat,
            $category
        );

        if ($report->isFallbackUsed()) {
            $note .= ' | Fallback=YES';
        }
        if ($report->getDecision() !== '') {
            $note .= ' | Decision=' . strtoupper($report->getDecision());
        }

        $sanitized = preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $note)) ?? '';
        $sanitized = trim($sanitized);

        return strlen($sanitized) <= self::MAX_LENGTH ? $sanitized : substr($sanitized, 0, self::MAX_LENGTH);
    }

    private function clamp01(float $value): float
    {
        return max(0.0, min(1.0, $value));
    }

    private function resolveThreatValue(string $threatTypes, string $linkThreat): string
    {
        $normalizedTypes = strtoupper(trim($threatTypes));
        if ($normalizedTypes !== '' && $normalizedTypes !== 'NONE') {
            return $normalizedTypes;
        }

        return strtoupper(trim($linkThreat)) === 'ERROR' ? 'ERROR' : 'NONE';
    }
}
