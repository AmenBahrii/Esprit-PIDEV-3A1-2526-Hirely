<?php

namespace App\Service;

use App\Entity\Application;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApplicationReviewAssistantService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Parser $pdfParser,
        private readonly string $groqApiKey,
        private readonly string $model = 'meta-llama/llama-4-scout-17b-16e-instruct',
        private readonly string $projectDir = '',
    ) {
    }

    /**
     * @return array{score:?int, reviewNote:?string}
     */
    public function generateReview(Application $application): array
    {
        if (trim($this->groqApiKey) === '') {
            throw new \RuntimeException('AI review is not configured yet. Add your Groq API key first.');
        }

        $jobOffer = $application->getJobOffer();

        if (!$jobOffer) {
            throw new \RuntimeException('The related job offer no longer exists, so AI review cannot compare the application properly.');
        }

        $resumeText = $this->readResumeText($application->getResumePath());
        if (mb_strlen($resumeText) > 12000) {
            $resumeText = mb_substr($resumeText, 0, 12000);
        }

        $prompt = <<<PROMPT
You are helping a recruiter review one application for one job offer.

Return only JSON with:
- score: integer from 0 to 100
- reviewNote: a concise professional recruiter review between 80 and 180 words

Rules:
- Score should reflect fit for the role, evidence from the application, and missing information.
- Be fair and grounded. Do not invent achievements.
- Mention strengths, concerns, and a recommendation.
- If information is missing, say so clearly in the note instead of guessing.

Job offer:
- Title: {$jobOffer->getTitle()}
- Contract type: {$jobOffer->getContractType()}
- Location: {$jobOffer->getLocation()}
- Salary: {$jobOffer->getSalary()}
- Experience required: {$jobOffer->getExperienceRequired()}
- Description: {$jobOffer->getDescription()}

Application:
- Candidate email: {$application->getEmail()}
- Phone: {$application->getPhone()}
- Expected salary: {$application->getExpectedSalary()}
- Experience years: {$application->getExperienceYears()}
- Availability date: {$application->getAvailabilityDate()?->format('Y-m-d')}
- Portfolio URL: {$application->getPortfolioUrl()}
- Cover letter: {$application->getCoverLetter()}

Resume text:
{$resumeText}
PROMPT;

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
                            'content' => 'You produce structured recruiter review suggestions for job applications.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'application_ai_review',
                            'strict' => false,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'score' => [
                                        'type' => ['integer', 'null'],
                                    ],
                                    'reviewNote' => [
                                        'type' => ['string', 'null'],
                                    ],
                                ],
                                'required' => ['score', 'reviewNote'],
                            ],
                        ],
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(false);
        } catch (\Throwable) {
            throw new \RuntimeException('AI review could not reach Groq. Please check the API key and try again.');
        }

        if ($statusCode >= 400) {
            $message = $payload['error']['message'] ?? 'AI review is not available right now.';

            if ($statusCode === 429) {
                $message = 'AI review is temporarily unavailable because the Groq rate limit was reached. Please try again in a moment.';
            }

            throw new \RuntimeException((string) $message);
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || $content === '') {
            throw new \RuntimeException('The AI review response was empty.');
        }

        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('The AI review response could not be understood.');
        }

        $score = $decoded['score'] ?? null;
        $note = $decoded['reviewNote'] ?? null;

        return [
            'score' => is_numeric($score) ? max(0, min(100, (int) $score)) : null,
            'reviewNote' => is_string($note) && trim($note) !== '' ? trim($note) : null,
        ];
    }

    private function readResumeText(?string $resumePath): string
    {
        if (!$resumePath || $this->projectDir === '') {
            return 'No resume text available.';
        }

        $fullPath = $this->projectDir.'/public/uploads/resumes/'.$resumePath;

        if (!is_file($fullPath)) {
            return 'No resume text available.';
        }

        $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => $this->extractPdfText($fullPath),
            'docx' => $this->extractDocxText($fullPath),
            'rtf' => $this->extractRtfText($fullPath),
            'doc' => $this->extractLegacyDocText($fullPath),
            'txt' => (string) file_get_contents($fullPath),
            default => 'No resume text available.',
        };
    }

    private function extractPdfText(string $path): string
    {
        return $this->pdfParser->parseFile($path)->getText();
    }

    private function extractDocxText(string $path): string
    {
        $document = IOFactory::load($path);
        $text = [];

        foreach ($document->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text[] = (string) $element->getText();
                    continue;
                }

                if (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $childElement) {
                        if (method_exists($childElement, 'getText')) {
                            $text[] = (string) $childElement->getText();
                        }
                    }
                }
            }
        }

        return implode("\n", array_filter(array_map('trim', $text)));
    }

    private function extractRtfText(string $path): string
    {
        $contents = (string) file_get_contents($path);
        $contents = preg_replace('/\\\\par[d]?/', "\n", $contents) ?? $contents;
        $contents = preg_replace('/\\\\[a-z]+-?\d* ?/i', ' ', $contents) ?? $contents;
        $contents = str_replace(['{', '}'], ' ', $contents);

        return trim(html_entity_decode(strip_tags($contents)));
    }

    private function extractLegacyDocText(string $path): string
    {
        $contents = (string) file_get_contents($path);
        $contents = preg_replace("/[\x00-\x08\x0B\x0C\x0E-\x1F]/", ' ', $contents) ?? $contents;
        $contents = preg_replace('/[^[:print:]\r\n\t]/u', ' ', $contents) ?? $contents;
        $contents = preg_replace('/\s+/', ' ', $contents) ?? $contents;

        return trim($contents);
    }
}
