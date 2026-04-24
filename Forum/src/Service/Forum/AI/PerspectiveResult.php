<?php

namespace App\Service\Forum\AI;

final class PerspectiveResult
{
    public function __construct(
        private readonly float $toxicity,
        private readonly string $raw,
        private readonly int $latencyMs,
    ) {
    }

    public function getToxicity(): float
    {
        return $this->toxicity;
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


