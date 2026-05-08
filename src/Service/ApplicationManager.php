<?php

namespace App\Service;

use App\Entity\Application;

class ApplicationManager
{
    public function validate(Application $application): bool
    {
        if (
            $application->getUser() !== null
            && trim((string) $application->getEmail()) !== trim((string) $application->getUser()->getEmail())
        ) {
            throw new \InvalidArgumentException('Application email must match the candidate email');
        }

        if (
            $application->getJobOffer() !== null
            && $application->getJobOffer()->getStatus() !== 'Open'
        ) {
            throw new \InvalidArgumentException('Applications are only allowed for open job offers');
        }

        if (
            $application->getJobOffer() !== null
            && $application->getExperienceYears() < $application->getJobOffer()->getExperienceRequired()
        ) {
            throw new \InvalidArgumentException('Candidate experience does not meet the job offer requirement');
        }

        if ($application->getExpectedSalary() < 0) {
            throw new \InvalidArgumentException('Expected salary cannot be negative');
        }

        if (!filter_var($application->getPortfolioUrl(), FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('Portfolio URL is invalid');
        }

        if ($application->getAvailabilityDate() < $application->getApplicationDate()) {
            throw new \InvalidArgumentException('Availability date cannot be before the application date');
        }

        return true;
    }
}
