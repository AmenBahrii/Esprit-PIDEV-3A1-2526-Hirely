<?php

namespace App\Service;

class FaceRecognitionService
{
    /**
     * @param array<int, mixed> $descriptor
     * @return array<int, float>
     */
    public function normalizeDescriptor(array $descriptor): array
    {
        $normalized = array_map(static fn ($value): float => (float) $value, $descriptor);

        if (count($normalized) < 64) {
            throw new \InvalidArgumentException('Invalid face descriptor payload.');
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $descriptor
     */
    public function serializeDescriptor(array $descriptor): string
    {
        return json_encode($this->normalizeDescriptor($descriptor), JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, float>|null
     */
    public function deserializeDescriptor(?string $faceData): ?array
    {
        if ($faceData === null || trim($faceData) === '') {
            return null;
        }

        $decoded = json_decode($faceData, true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($decoded)) {
            return null;
        }

        return $this->normalizeDescriptor($decoded);
    }

    /**
     * @param array<int, mixed> $probeDescriptor
     */
    public function matchesStoredDescriptor(?string $faceData, array $probeDescriptor, float $threshold = 0.45): bool
    {
        $storedDescriptor = $this->deserializeDescriptor($faceData);

        if ($storedDescriptor === null) {
            return false;
        }

        $probe = $this->normalizeDescriptor($probeDescriptor);

        if (count($storedDescriptor) !== count($probe)) {
            return false;
        }

        return $this->calculateDistance($storedDescriptor, $probe) <= $threshold;
    }

    /**
     * @param array<int, float> $left
     * @param array<int, float> $right
     */
    private function calculateDistance(array $left, array $right): float
    {
        $sum = 0.0;

        foreach ($left as $index => $value) {
            $delta = $value - $right[$index];
            $sum += $delta * $delta;
        }

        return sqrt($sum);
    }
}
