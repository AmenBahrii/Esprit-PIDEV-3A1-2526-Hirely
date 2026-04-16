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
            "Hello %s,\n\nA new candidate has applied to your job offer \"%s\".\n\nCandidate: %s %s\nEmail: %s\nPhone: %s\nExpected salary: %s\nExperience: %s year(s)\nPortfolio: %s\n\nYou can review the application in Hirely.\n",
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

        $html = sprintf(
            '<p>Hello %s,</p><p>A new candidate has applied to your job offer "<strong>%s</strong>".</p><ul><li><strong>Candidate:</strong> %s %s</li><li><strong>Email:</strong> %s</li><li><strong>Phone:</strong> %s</li><li><strong>Expected salary:</strong> %s</li><li><strong>Experience:</strong> %s year(s)</li><li><strong>Portfolio:</strong> <a href="%s">%s</a></li></ul><p>You can review the application in Hirely.</p>',
            htmlspecialchars((string) $recruiter->getFirstName(), ENT_QUOTES),
            htmlspecialchars((string) $jobOffer->getTitle(), ENT_QUOTES),
            htmlspecialchars((string) $candidate->getFirstName(), ENT_QUOTES),
            htmlspecialchars((string) $candidate->getLastName(), ENT_QUOTES),
            htmlspecialchars((string) $application->getEmail(), ENT_QUOTES),
            htmlspecialchars((string) $application->getPhone(), ENT_QUOTES),
            htmlspecialchars((string) $application->getExpectedSalary(), ENT_QUOTES),
            htmlspecialchars((string) $application->getExperienceYears(), ENT_QUOTES),
            htmlspecialchars((string) $application->getPortfolioUrl(), ENT_QUOTES),
            htmlspecialchars((string) $application->getPortfolioUrl(), ENT_QUOTES)
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

        $html = sprintf(
            '<p>Hello %s,</p><p>Your application for "<strong>%s</strong>" has been <strong>%s</strong>.</p>',
            htmlspecialchars((string) $candidate->getFirstName(), ENT_QUOTES),
            htmlspecialchars((string) $jobOffer->getTitle(), ENT_QUOTES),
            htmlspecialchars((string) $decision, ENT_QUOTES)
        );

        if ($score !== null) {
            $body .= sprintf("\nScore: %s/100\n", (string) $score);
            $html .= sprintf('<p><strong>Score:</strong> %s/100</p>', htmlspecialchars((string) $score, ENT_QUOTES));
        }

        if ($reviewNote !== '') {
            $body .= sprintf("\nReview note:\n%s\n", $reviewNote);
            $html .= sprintf('<p><strong>Review note:</strong><br>%s</p>', nl2br(htmlspecialchars($reviewNote, ENT_QUOTES)));
        }

        if ($recruiter !== null) {
            $body .= sprintf("\nRecruiter: %s %s\n", $recruiter->getFirstName(), $recruiter->getLastName());
            $html .= sprintf(
                '<p><strong>Recruiter:</strong> %s %s</p>',
                htmlspecialchars((string) $recruiter->getFirstName(), ENT_QUOTES),
                htmlspecialchars((string) $recruiter->getLastName(), ENT_QUOTES)
            );
        }

        $body .= "\nLog in to Hirely to view more details.\n";
        $html .= '<p>Log in to Hirely to view more details.</p>';

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
            $this->mailer->send(
                (new Email())
                    ->from(new Address($this->fromAddress, 'Hirely'))
                    ->to($toAddress)
                    ->subject($subject)
                    ->text($textBody)
                    ->html($htmlBody)
            );
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Application email notification failed.', [
                'to' => $toAddress,
                'subject' => $subject,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
