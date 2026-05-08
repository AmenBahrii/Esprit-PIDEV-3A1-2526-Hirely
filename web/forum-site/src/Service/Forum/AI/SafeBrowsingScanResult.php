<?php

namespace App\Service\Forum\AI;

final class SafeBrowsingScanResult
{
    public function __construct(
        private readonly string $linkThreat,
        private readonly string $threatTypes,
    ) {
    }

    public function getLinkThreat(): string
    {
        return $this->linkThreat;
    }

    public function getThreatTypes(): string
    {
        return $this->threatTypes;
    }
}
