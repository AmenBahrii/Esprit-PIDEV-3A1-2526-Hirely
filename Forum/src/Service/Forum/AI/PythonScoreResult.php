<?php

namespace App\Service\Forum\AI;

final class PythonScoreResult
{
    /**
     * @param list<string> $relevanceReasons
     * @param list<string> $qualityReasons
     * @param list<string> $duplicateReasons
     */
    public function __construct(
        private readonly float $relevance,
        private readonly string $predictedCategory,
        private readonly float $quality,
        private readonly float $duplicateScore,
        private readonly ?int $duplicateOfPostId,
        private readonly array $relevanceReasons,
        private readonly array $qualityReasons,
        private readonly array $duplicateReasons,
        private readonly string $raw,
        private readonly int $latencyMs,
    ) {
    }

    public function getRelevance(): float
    {
        return $this->relevance;
    }

    public function getPredictedCategory(): string
    {
        return $this->predictedCategory;
    }

    public function getQuality(): float
    {
        return $this->quality;
    }

    public function getDuplicateScore(): float
    {
        return $this->duplicateScore;
    }

    public function getDuplicateOfPostId(): ?int
    {
        return $this->duplicateOfPostId;
    }

    /**
     * @return list<string>
     */
    public function getRelevanceReasons(): array
    {
        return $this->relevanceReasons;
    }

    /**
     * @return list<string>
     */
    public function getQualityReasons(): array
    {
        return $this->qualityReasons;
    }

    /**
     * @return list<string>
     */
    public function getDuplicateReasons(): array
    {
        return $this->duplicateReasons;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    public function getLatencyMs(): int
    {
        return $this->latencyMs;
    }
}


