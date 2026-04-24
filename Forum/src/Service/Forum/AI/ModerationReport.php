<?php

namespace App\Service\Forum\AI;

final class ModerationReport
{
    private string $decision = 'PENDING';
    private float $toxicity = 0.0;
    private float $relevance = 0.0;
    private string $predictedCategory = 'General';
    private float $qualityScore = 0.0;
    private float $duplicateScore = 0.0;
    private ?int $duplicateOfPostId = null;
    private string $linkThreat = 'NONE';
    private string $linkThreatTypes = 'NONE';
    private bool $fallbackUsed = false;
    /** @var list<string> */
    private array $reasons = [];
    private string $pythonRaw = '';
    private string $perspectiveRaw = '';
    private int $pythonLatencyMs = 0;
    private int $perspectiveLatencyMs = 0;
    private int $totalLatencyMs = 0;

    public function getDecision(): string
    {
        return $this->decision;
    }

    public function setDecision(string $decision): self
    {
        $this->decision = strtoupper(trim($decision)) ?: 'PENDING';

        return $this;
    }

    public function getToxicity(): float
    {
        return $this->toxicity;
    }

    public function setToxicity(float $toxicity): self
    {
        $this->toxicity = $toxicity;

        return $this;
    }

    public function getRelevance(): float
    {
        return $this->relevance;
    }

    public function setRelevance(float $relevance): self
    {
        $this->relevance = $relevance;

        return $this;
    }

    public function getPredictedCategory(): string
    {
        return $this->predictedCategory;
    }

    public function setPredictedCategory(string $predictedCategory): self
    {
        $this->predictedCategory = trim($predictedCategory) !== '' ? trim($predictedCategory) : 'General';

        return $this;
    }

    public function getQualityScore(): float
    {
        return $this->qualityScore;
    }

    public function setQualityScore(float $qualityScore): self
    {
        $this->qualityScore = $qualityScore;

        return $this;
    }

    public function getDuplicateScore(): float
    {
        return $this->duplicateScore;
    }

    public function setDuplicateScore(float $duplicateScore): self
    {
        $this->duplicateScore = $duplicateScore;

        return $this;
    }

    public function getDuplicateOfPostId(): ?int
    {
        return $this->duplicateOfPostId;
    }

    public function setDuplicateOfPostId(?int $duplicateOfPostId): self
    {
        $this->duplicateOfPostId = $duplicateOfPostId;

        return $this;
    }

    public function getLinkThreat(): string
    {
        return $this->linkThreat;
    }

    public function setLinkThreat(string $linkThreat): self
    {
        $this->linkThreat = strtoupper(trim($linkThreat)) ?: 'NONE';

        return $this;
    }

    public function getLinkThreatTypes(): string
    {
        return $this->linkThreatTypes;
    }

    public function setLinkThreatTypes(string $linkThreatTypes): self
    {
        $this->linkThreatTypes = strtoupper(trim($linkThreatTypes)) ?: 'NONE';

        return $this;
    }

    public function isFallbackUsed(): bool
    {
        return $this->fallbackUsed;
    }

    public function setFallbackUsed(bool $fallbackUsed): self
    {
        $this->fallbackUsed = $fallbackUsed;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getReasons(): array
    {
        return $this->reasons;
    }

    /**
     * @param list<string> $reasons
     */
    public function setReasons(array $reasons): self
    {
        $this->reasons = array_values(array_filter(array_map(
            static fn (mixed $reason): string => trim((string) $reason),
            $reasons
        )));

        return $this;
    }

    public function addReason(string $reason): self
    {
        $reason = trim($reason);
        if ($reason !== '') {
            $this->reasons[] = $reason;
        }

        return $this;
    }

    public function getPythonRaw(): string
    {
        return $this->pythonRaw;
    }

    public function setPythonRaw(string $pythonRaw): self
    {
        $this->pythonRaw = $pythonRaw;

        return $this;
    }

    public function getPerspectiveRaw(): string
    {
        return $this->perspectiveRaw;
    }

    public function setPerspectiveRaw(string $perspectiveRaw): self
    {
        $this->perspectiveRaw = $perspectiveRaw;

        return $this;
    }

    public function getPythonLatencyMs(): int
    {
        return $this->pythonLatencyMs;
    }

    public function setPythonLatencyMs(int $pythonLatencyMs): self
    {
        $this->pythonLatencyMs = max(0, $pythonLatencyMs);

        return $this;
    }

    public function getPerspectiveLatencyMs(): int
    {
        return $this->perspectiveLatencyMs;
    }

    public function setPerspectiveLatencyMs(int $perspectiveLatencyMs): self
    {
        $this->perspectiveLatencyMs = max(0, $perspectiveLatencyMs);

        return $this;
    }

    public function getTotalLatencyMs(): int
    {
        return $this->totalLatencyMs;
    }

    public function setTotalLatencyMs(int $totalLatencyMs): self
    {
        $this->totalLatencyMs = max(0, $totalLatencyMs);

        return $this;
    }
}


