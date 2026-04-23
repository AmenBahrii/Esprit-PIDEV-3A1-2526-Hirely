<?php

namespace App\Service;

use App\Entity\Joboffer;
use App\Entity\User;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ResumeAutofillService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Parser $pdfParser,
        private readonly string $groqApiKey,
        private readonly string $model = 'meta-llama/llama-4-scout-17b-16e-instruct',
    ) {
    }

    /**
     * @return array{phone:?string, portfolioUrl:?string, experienceYears:?int, coverLetter:?string}
     */
    public function extractApplicationDraft(UploadedFile $resumeFile, User $candidate, Joboffer $jobOffer): array
    {
        if (trim($this->groqApiKey) === '') {
            throw new \RuntimeException('Resume autofill is not configured yet. Add your Groq API key first.');
        }

        $resumeText = $this->extractTextFromResume($resumeFile);
        $resumeText = trim($resumeText);

        if ($resumeText === '') {
            throw new \RuntimeException('We could not read any text from that resume. Try a clearer PDF or DOCX file.');
        }

        if (mb_strlen($resumeText) > 18000) {
            $resumeText = mb_substr($resumeText, 0, 18000);
        }

        $jobContext = sprintf(
            "Job title: %s\nContract type: %s\nLocation: %s\nExperience required: %s\nDescription: %s",
            $jobOffer->getTitle(),
            $jobOffer->getContractType(),
            $jobOffer->getLocation(),
            (string) $jobOffer->getExperienceRequired(),
            $jobOffer->getDescription()
        );

        $prompt = <<<PROMPT
Extract application draft data from the candidate resume.

Rules:
- Return only JSON matching the schema.
- Do not invent details that are not supported by the resume text.
- If a phone number or portfolio URL is not present, return null for that field.
- Estimate experienceYears conservatively from the resume when the work history makes it reasonably clear; otherwise return null.
- Write coverLetter as a concise first-person application message tailored to the provided job offer, using only facts supported by the resume.
- Keep the cover letter between 90 and 160 words.
- The candidate account email is {$candidate->getEmail()}; do not include a different email.

Job offer:
{$jobContext}

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
                            'content' => 'You extract structured application draft data from resumes for a recruitment platform.',
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'response_format' => [
                        'type' => 'json_schema',
                        'json_schema' => [
                            'name' => 'application_resume_autofill',
                            'strict' => false,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'phone' => [
                                        'type' => ['string', 'null'],
                                    ],
                                    'portfolioUrl' => [
                                        'type' => ['string', 'null'],
                                    ],
                                    'experienceYears' => [
                                        'type' => ['integer', 'null'],
                                    ],
                                    'coverLetter' => [
                                        'type' => ['string', 'null'],
                                    ],
                                ],
                                'required' => ['phone', 'portfolioUrl', 'experienceYears', 'coverLetter'],
                            ],
                        ],
                    ],
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $payload = $response->toArray(false);
        } catch (\Throwable) {
            throw new \RuntimeException('Resume autofill could not reach Groq. Please check the API key and try again.');
        }

        if ($statusCode >= 400) {
            $message = $payload['error']['message'] ?? 'Resume autofill is not available right now.';

            if ($statusCode === 429) {
                $message = 'Resume autofill is temporarily unavailable because the Groq rate limit was reached. Please try again in a moment.';
            }

            throw new \RuntimeException((string) $message);
        }

        $content = $payload['choices'][0]['message']['content'] ?? null;

        if (!is_string($content) || $content === '') {
            throw new \RuntimeException('The AI response was empty, so no fields could be filled.');
        }

        /** @var array{phone:mixed,portfolioUrl:mixed,experienceYears:mixed,coverLetter:mixed}|null $decoded */
        $decoded = json_decode($content, true);

        if (!is_array($decoded)) {
            throw new \RuntimeException('The AI response was unreadable, so no fields could be filled.');
        }

        return [
            'phone' => $this->normalizeNullableString($decoded['phone'] ?? null),
            'portfolioUrl' => $this->normalizeNullableString($decoded['portfolioUrl'] ?? null),
            'experienceYears' => $this->normalizeNullableInt($decoded['experienceYears'] ?? null),
            'coverLetter' => $this->normalizeNullableString($decoded['coverLetter'] ?? null),
        ];
    }

    private function extractTextFromResume(UploadedFile $resumeFile): string
    {
        $extension = strtolower($resumeFile->getClientOriginalExtension());
        $path = $resumeFile->getPathname();

        return match ($extension) {
            'pdf' => $this->extractPdfText($path),
            'docx' => $this->extractDocxText($path),
            'rtf' => $this->extractRtfText($path),
            'doc' => $this->extractLegacyDocText($path),
            'txt' => (string) file_get_contents($path),
            default => throw new \RuntimeException('Resume autofill currently supports PDF, DOCX, DOC, RTF, or TXT files.'),
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

    private function normalizeNullableString(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        return null;
    }
}
