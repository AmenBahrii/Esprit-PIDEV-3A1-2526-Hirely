<?php

namespace App\Service\Forum\AI;

use App\Entity\ForumPost;
use App\Repository\ForumPostRepository;

final class ModerationEngine
{
    public const TYPE_POST = 'post';
    public const TYPE_COMMENT = 'comment';

    private const STATUS_APPROVED = 'APPROVED';
    private const STATUS_PENDING = 'PENDING';
    private const STATUS_REJECTED = 'REJECTED';
    private const DUPLICATE_PENDING_THRESHOLD = 0.80;

    public function __construct(
        private readonly PerspectiveClient $perspectiveClient,
        private readonly PythonAiClient $pythonAiClient,
        private readonly ForumPostRepository $postRepository,
        private readonly SafeBrowsingClient $safeBrowsingClient,
    ) {
    }

    public function analyze(string $type, string $text, ?string $contentKey = null): ModerationReport
    {
        $safeType = strtolower(trim($type)) === self::TYPE_COMMENT ? self::TYPE_COMMENT : self::TYPE_POST;
        $safeText = trim($text);
        $report = (new ModerationReport())
            ->setPredictedCategory('General')
            ->setDuplicateScore(0.0)
            ->setDuplicateOfPostId(null);
        $startedAt = microtime(true);
        $fallback = false;

        try {
            $perspective = $this->perspectiveClient->analyze($safeText);
            $report
                ->setToxicity($perspective->getToxicity())
                ->setPerspectiveRaw($perspective->getRaw())
                ->setPerspectiveLatencyMs($perspective->getLatencyMs());
        } catch (\Throwable $exception) {
            $fallback = true;
            $report
                ->setToxicity(0.5)
                ->setPerspectiveRaw('Perspective unavailable')
                ->addReason(str_contains($exception->getMessage(), 'YOUR_KEY_HERE') ? 'Replace YOUR_KEY_HERE' : $this->prefixReason('Perspective API unavailable', $exception));
        }

        try {
            $score = $this->pythonAiClient->score($safeText, $safeType, $contentKey ?? $this->buildContentKey($safeType, $safeText));
            $report
                ->setRelevance($score->getRelevance())
                ->setPredictedCategory($score->getPredictedCategory())
                ->setQualityScore($score->getQuality())
                ->setDuplicateScore($score->getDuplicateScore())
                ->setDuplicateOfPostId($score->getDuplicateOfPostId())
                ->setPythonRaw('score=' . $score->getRaw())
                ->setPythonLatencyMs($score->getLatencyMs())
                ->setReasons(array_merge(
                    $report->getReasons(),
                    $score->getRelevanceReasons(),
                    $score->getQualityReasons(),
                    $score->getDuplicateReasons(),
                ));
        } catch (\Throwable $exception) {
            $fallback = true;
            $report
                ->setRelevance(0.5)
                ->setPredictedCategory('General')
                ->setQualityScore(0.5)
                ->setDuplicateScore(0.0)
                ->setDuplicateOfPostId(null)
                ->setPythonRaw('Python services unavailable')
                ->addReason($this->prefixReason('Python scoring service unavailable', $exception));

            if ($safeType === self::TYPE_POST) {
                [$duplicateScore, $duplicateOfPostId] = $this->computeOfflineDuplicate($safeText);
                $report
                    ->setDuplicateScore($duplicateScore)
                    ->setDuplicateOfPostId($duplicateOfPostId);

                if ($duplicateScore >= self::DUPLICATE_PENDING_THRESHOLD) {
                    $report->addReason($duplicateOfPostId !== null
                        ? sprintf('Offline duplicate check found strong overlap (%.2f) with post #%d', $duplicateScore, $duplicateOfPostId)
                        : sprintf('Offline duplicate check found strong overlap (%.2f)', $duplicateScore));
                }
            }
        }

        $safeBrowsing = $this->safeBrowsingClient->scanTextDetailed($safeText);
        $report
            ->setLinkThreat($safeBrowsing->getLinkThreat())
            ->setLinkThreatTypes($safeBrowsing->getThreatTypes());

        $decision = $fallback
            ? self::STATUS_PENDING
            : $this->decideWithPolicy($safeType, $report);

        if ($fallback) {
            $report
                ->setFallbackUsed(true)
                ->addReason('AI unavailable - sent for manual review');
        }

        $report
            ->setDecision($this->enforceLinkThreatPolicy($decision, $report))
            ->setTotalLatencyMs((int) round((microtime(true) - $startedAt) * 1000));

        return $report;
    }

    public function analyzePost(string $text, ?string $contentKey = null): ModerationReport
    {
        return $this->analyze(self::TYPE_POST, $text, $contentKey);
    }

    public function analyzeComment(string $text, ?string $contentKey = null): ModerationReport
    {
        return $this->analyze(self::TYPE_COMMENT, $text, $contentKey);
    }

    private function decideWithPolicy(string $type, ModerationReport $report): string
    {
        $toxicity = $report->getToxicity();
        $relevance = $report->getRelevance();
        $quality = $report->getQualityScore();
        $duplicate = $report->getDuplicateScore();

        if ($type === self::TYPE_POST) {
            if ($toxicity >= 0.80) {
                $report->addReason('High toxicity score');

                return self::STATUS_REJECTED;
            }
            if ($duplicate >= self::DUPLICATE_PENDING_THRESHOLD) {
                $report->addReason(sprintf('Possible duplicate content (score %.2f)', $duplicate));

                return self::STATUS_PENDING;
            }
            if ($toxicity <= 0.20 && $relevance >= 0.70 && $quality >= 0.55 && $duplicate <= 0.90) {
                $report->addReason('Passed post auto-approval policy');

                return self::STATUS_APPROVED;
            }
            if ($relevance < 0.35) {
                $report->addReason('Low career relevance');
            }

            return self::STATUS_PENDING;
        }

        if ($toxicity >= 0.80) {
            $report->addReason('High toxicity score');

            return self::STATUS_REJECTED;
        }
        if ($toxicity <= 0.25 && $relevance >= 0.45 && $quality >= 0.40 && $duplicate <= 0.93) {
            $report->addReason('Passed comment auto-approval policy');

            return self::STATUS_APPROVED;
        }

        return self::STATUS_PENDING;
    }

    private function enforceLinkThreatPolicy(string $decision, ModerationReport $report): string
    {
        $linkThreat = strtoupper($report->getLinkThreat());
        if ($linkThreat === 'FLAGGED') {
            $report->addReason('Safe Browsing flagged one or more URLs');

            return self::STATUS_PENDING;
        }
        if ($linkThreat === 'ERROR') {
            $report->addReason('Safe Browsing URL scan failed');

            return self::STATUS_PENDING;
        }

        return $decision;
    }

    /**
     * @return array{0: float, 1: ?int}
     */
    private function computeOfflineDuplicate(string $submittedText): array
    {
        $submittedTokens = $this->tokenize($submittedText);
        if ($submittedTokens === []) {
            return [0.0, null];
        }

        $bestScore = 0.0;
        $bestPostId = null;
        foreach ($this->postRepository->findRecentForDuplicateComparison() as $post) {
            if (!$post instanceof ForumPost || $post->getId() === null) {
                continue;
            }
            $candidateTokens = $this->tokenize(trim($post->getTitle() . ' ' . $post->getContent()));
            $score = $this->tokenOverlap($submittedTokens, $candidateTokens);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestPostId = $post->getId();
            }
        }

        return [$bestScore, $bestPostId];
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        preg_match_all('/[a-z0-9]{2,}/i', strtolower($text), $matches);
        $tokens = array_values(array_unique($matches[0] ?? []));

        return array_values(array_filter($tokens));
    }

    /**
     * @param list<string> $left
     * @param list<string> $right
     */
    private function tokenOverlap(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }
        $intersection = count(array_intersect($left, $right));
        $union = count(array_unique(array_merge($left, $right)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    private function prefixReason(string $prefix, \Throwable $exception): string
    {
        $message = trim(str_replace(["\r", "\n"], ' ', $exception->getMessage()));
        $message = $message !== '' ? $message : $exception::class;

        if (strlen($message) > 120) {
            $message = substr($message, 0, 120) . '...';
        }

        return $prefix . ': ' . $message;
    }

    private function buildContentKey(string $type, string $text): string
    {
        return sprintf('%s:%s:%d', $type, substr(sha1($text), 0, 12), time());
    }
}


