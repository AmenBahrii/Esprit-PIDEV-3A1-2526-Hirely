<?php

namespace App\Service;

use App\Entity\Application;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class ApplicationNotificationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $fromAddress,
        private readonly string $projectDir,
    ) {
    }

    public function sendApplicationSubmittedToRecruiter(Application $application): void
    {
        $candidate = $application->getUser();
        $jobOffer = $application->getJobOffer();
        $recruiter = $jobOffer?->getUser();

        if ($candidate === null || $jobOffer === null || $recruiter === null) {
            return;
        }

        $subject = sprintf('New application for %s', $jobOffer->getTitle());
        $body = sprintf(
            "Hello %s,\n\nA new candidate has applied to your job offer \"%s\".\n\nCandidate: %s %s\nEmail: %s\nPhone: %s\nExpected salary: %s\nExperience: %s year(s)\nPortfolio: %s\n\nOpen Hirely to review the application and continue the hiring process.\n",
            $recruiter->getFirstName(),
            $jobOffer->getTitle(),
            $candidate->getFirstName(),
            $candidate->getLastName(),
            $application->getEmail(),
            $application->getPhone(),
            (string) $application->getExpectedSalary(),
            (string) $application->getExperienceYears(),
            $application->getPortfolioUrl()
        );

        $html = $this->renderEmailLayout(
            'New Application Received',
            sprintf(
                'Hello %s, a new candidate has applied to your job offer <strong>%s</strong>.',
                $this->escape((string) $recruiter->getFirstName()),
                $this->escape((string) $jobOffer->getTitle())
            ),
            [
                'Candidate' => trim($candidate->getFirstName() . ' ' . $candidate->getLastName()),
                'Email' => (string) $application->getEmail(),
                'Phone' => (string) $application->getPhone(),
                'Expected salary' => (string) $application->getExpectedSalary(),
                'Experience' => sprintf('%s year(s)', (string) $application->getExperienceYears()),
                'Portfolio' => $application->getPortfolioUrl()
                    ? sprintf(
                        '<a href="%s" style="color:#c9570c; text-decoration:none;">%s</a>',
                        $this->escape((string) $application->getPortfolioUrl()),
                        $this->escape((string) $application->getPortfolioUrl())
                    )
                    : 'Not provided',
            ],
            'Open Hirely to review the application and continue the hiring process.'
        );

        $this->deliver($recruiter->getEmail(), $subject, $body, $html);
    }

    public function sendDecisionToCandidate(Application $application): void
    {
        $candidate = $application->getUser();
        $jobOffer = $application->getJobOffer();
        $recruiter = $jobOffer?->getUser();

        if ($candidate === null || $jobOffer === null) {
            return;
        }

        $decision = $application->getCurrentStatus();
        $reviewNote = trim((string) $application->getReviewNote());
        $score = $application->getScore();

        $body = sprintf(
            "Hello %s,\n\nYour application for \"%s\" has been %s.\n",
            $candidate->getFirstName(),
            $jobOffer->getTitle(),
            $decision
        );

        $details = [
            'Position' => (string) $jobOffer->getTitle(),
            'Decision' => (string) $decision,
        ];

        if ($score !== null) {
            $body .= sprintf("\nScore: %s/100\n", (string) $score);
            $details['Score'] = sprintf('%s/100', (string) $score);
        }

        if ($reviewNote !== '') {
            $body .= sprintf("\nReview note:\n%s\n", $reviewNote);
            $details['Review note'] = nl2br($this->escape($reviewNote));
        }

        if ($recruiter !== null) {
            $body .= sprintf("\nRecruiter: %s %s\n", $recruiter->getFirstName(), $recruiter->getLastName());
            $details['Recruiter'] = trim($recruiter->getFirstName() . ' ' . $recruiter->getLastName());
        }

        $body .= "\nLog in to Hirely to view more details.\n";
        $html = $this->renderEmailLayout(
            'Application Status Update',
            sprintf(
                'Hello %s, your application for <strong>%s</strong> has been <strong>%s</strong>.',
                $this->escape((string) $candidate->getFirstName()),
                $this->escape((string) $jobOffer->getTitle()),
                $this->escape((string) $decision)
            ),
            $details,
            'Log in to Hirely to view more details and keep track of your applications.'
        );

        $this->deliver(
            $candidate->getEmail(),
            sprintf('Application %s: %s', $decision, $jobOffer->getTitle()),
            $body,
            $html
        );
    }

    private function deliver(string $toAddress, string $subject, string $textBody, string $htmlBody): void
    {
        try {
            $email = (new Email())
                ->from(new Address($this->fromAddress, 'Hirely'))
                ->to($toAddress)
                ->subject($subject)
                ->text($textBody);

            $logoPath = $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'logo.png';
            if (is_file($logoPath)) {
                $email->embedFromPath($logoPath, 'hirely-logo');
            }

            $email->html($htmlBody);

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Application email notification failed.', [
                'to' => $toAddress,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function renderEmailLayout(string $eyebrow, string $intro, array $details, string $closing): string
    {
        $rows = [];
        foreach ($details as $label => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $rows[] = sprintf(
                '<tr><td style="padding:0 0 12px; color:#6b7280; font-size:13px; font-weight:700; width:150px; vertical-align:top;">%s</td><td style="padding:0 0 12px; color:#1f2937; font-size:14px; line-height:1.6;">%s</td></tr>',
                $this->escape((string) $label),
                (string) $value
            );
        }

        return sprintf(
            '<!DOCTYPE html>
<html lang="en">
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" style="width:100%%; border-collapse:collapse; background:#f4f6f8; padding:24px 0;" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table role="presentation" style="width:100%%; max-width:640px; border-collapse:collapse;" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="padding:0 20px 16px;" align="center">
                            <table role="presentation" style="width:100%%; border-collapse:collapse; background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 18px 40px rgba(15,23,42,0.08);" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:28px 32px 20px; background:linear-gradient(135deg, #fff8f4 0%%, #ffffff 55%%); border-bottom:1px solid #edf2f7;">
                                        <table role="presentation" style="width:100%%; border-collapse:collapse;" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="vertical-align:middle;">
                                                    <img src="cid:hirely-logo" alt="Hirely" style="height:46px; display:block; margin-bottom:18px;">
                                                    <div style="color:#c9570c; font-size:12px; font-weight:700; letter-spacing:0.08em; text-transform:uppercase; margin-bottom:10px;">%s</div>
                                                    <div style="font-size:24px; line-height:1.35; font-weight:700; color:#111827;">Hirely</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:28px 32px 18px;">
                                        <div style="font-size:15px; line-height:1.75; color:#374151; margin-bottom:20px;">%s</div>
                                        <table role="presentation" style="width:100%%; border-collapse:collapse; background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:18px;" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:18px 18px 6px;">
                                                    <table role="presentation" style="width:100%%; border-collapse:collapse;" cellpadding="0" cellspacing="0">
                                                        %s
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        <div style="font-size:14px; line-height:1.75; color:#4b5563; margin-top:20px;">%s</div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:0 32px 26px; color:#9ca3af; font-size:12px; line-height:1.7;">
                                        This email was sent by Hirely. Please do not reply directly to this message.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>',
            $this->escape($eyebrow),
            $intro,
            implode('', $rows),
            $this->escape($closing)
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
