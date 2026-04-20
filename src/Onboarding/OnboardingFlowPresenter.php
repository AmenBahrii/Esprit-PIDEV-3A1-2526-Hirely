<?php

namespace App\Onboarding;

final class OnboardingFlowPresenter
{
    public function __construct(
        private readonly OnboardingLanguageContext $onboardingLanguageContext,
    ) {
    }

    /**
     * @param array{
     *     currentPhase: array{code: string, phaseLabel: string, status: string},
     *     timeline: array<int, array{
     *         code: string,
     *         phaseLabel: string,
     *         status: string,
     *         taskCount: int,
     *         completedCount: int,
     *         progressPercent: int,
     *         primaryTaskId: int|null,
     *         primaryTaskTitle: string|null
     *     }>,
     *     nextActions: array<int, array{
     *         taskId: int|null,
     *         title: string,
     *         detail: string,
     *         priority: string,
     *         actionType: string
     *     }>,
     *     riskSignals: array<int, array{
     *         code: string,
     *         label: string,
     *         severity: string,
     *         count: int
     *     }>,
     *     smartSummary: string
     * } $flow
     * @return array<string, mixed>
     */
    public function translateFlow(array $flow, string $language): array
    {
        $translated = $flow;
        $phaseTexts = [
            'pre_arrival' => 'Pre-arrival',
            'first_day' => 'First day',
            'first_week' => 'First week',
            'completion' => 'Completion',
            'upcoming' => 'Upcoming',
            'active' => 'Active',
            'at_risk' => 'At Risk',
            'completed' => 'Completed',
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];

        $translatedPhaseTexts = $this->onboardingLanguageContext->translateMap($phaseTexts, $language);
        $summaryMap = $this->onboardingLanguageContext->translateMap([
            'summary' => $flow['smartSummary'],
        ], $language);
        $translated['smartSummary'] = $summaryMap['summary'] ?? $flow['smartSummary'];

        $translated['currentPhase']['phaseLabel'] = $translatedPhaseTexts[$flow['currentPhase']['code']] ?? $flow['currentPhase']['phaseLabel'];
        $translated['currentPhase']['statusLabel'] = $translatedPhaseTexts[$flow['currentPhase']['status']] ?? ucfirst(str_replace('_', ' ', $flow['currentPhase']['status']));
        $translated['riskLevelLabel'] = $translatedPhaseTexts[$flow['riskLevel']] ?? ucfirst($flow['riskLevel']);

        foreach ($translated['timeline'] as $index => $phase) {
            $translated['timeline'][$index]['phaseLabel'] = $translatedPhaseTexts[$phase['code']] ?? $phase['phaseLabel'];
            $translated['timeline'][$index]['statusLabel'] = $translatedPhaseTexts[$phase['status']] ?? ucfirst(str_replace('_', ' ', $phase['status']));

            if ($phase['primaryTaskTitle']) {
                $translated['timeline'][$index]['primaryTaskTitle'] = $this->onboardingLanguageContext->translateMap([
                    'title' => $phase['primaryTaskTitle'],
                ], $language)['title'] ?? $phase['primaryTaskTitle'];
            }
        }

        foreach ($translated['riskSignals'] as $index => $signal) {
            $translated['riskSignals'][$index]['label'] = $this->onboardingLanguageContext->translateMap([
                'label' => $signal['label'],
            ], $language)['label'] ?? $signal['label'];
            $translated['riskSignals'][$index]['severityLabel'] = $translatedPhaseTexts[$signal['severity']] ?? ucfirst($signal['severity']);
        }

        foreach ($translated['nextActions'] as $index => $action) {
            $actionTranslations = $this->onboardingLanguageContext->translateMap([
                'title' => $action['title'],
                'detail' => $action['detail'],
            ], $language);
            $translated['nextActions'][$index]['title'] = $actionTranslations['title'] ?? $action['title'];
            $translated['nextActions'][$index]['detail'] = $actionTranslations['detail'] ?? $action['detail'];
            $translated['nextActions'][$index]['priorityLabel'] = $translatedPhaseTexts[$action['priority']] ?? ucfirst($action['priority']);
        }

        return $translated;
    }
}
