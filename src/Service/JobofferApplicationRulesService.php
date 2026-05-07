<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Joboffer;
use App\Entity\Users;

class JobofferApplicationRulesService
{
    public function assertJobOfferCanBePublished(Joboffer $joboffer): bool
    {
        $owner = $joboffer->getUser();

        if (!$owner instanceof Users || !in_array('ROLE_RECRUITER', $owner->getRoles(), true)) {
            throw new \InvalidArgumentException('Only recruiters can own job offers.');
        }

        if ($joboffer->getContractType() === 'Internship' && $joboffer->getExperienceRequired() > 2) {
            throw new \InvalidArgumentException('Internships cannot require more than 2 years of experience.');
        }

        return true;
    }

    public function assertApplicationCanBeSubmitted(Application $application): bool
    {
        $jobOffer = $application->getJobOffer();
        $candidate = $application->getUser();

        if (!$jobOffer instanceof Joboffer) {
            throw new \InvalidArgumentException('A job offer is required for the application.');
        }

        if (!$candidate instanceof Users) {
            throw new \InvalidArgumentException('A candidate is required for the application.');
        }

        if (strcasecmp($jobOffer->getStatus(), 'Open') !== 0) {
            throw new \InvalidArgumentException('You can only apply to open job offers.');
        }

        if (strcasecmp($application->getEmail(), $candidate->getEmail()) !== 0) {
            throw new \InvalidArgumentException('Application email must match the candidate account email.');
        }

        if ($application->getAvailabilityDate() < $application->getApplicationDate()) {
            throw new \InvalidArgumentException('Availability date cannot be before the application date.');
        }

        if ($application->getAvailabilityDate() < $jobOffer->getPublicationDate()) {
            throw new \InvalidArgumentException('Availability date cannot be before the publication date of the selected job offer.');
        }

        if ($application->getExperienceYears() < $jobOffer->getExperienceRequired()) {
            throw new \InvalidArgumentException('Candidate experience must meet the job offer requirement.');
        }

        return true;
    }

    public function canCandidateDelete(Application $application, Users $candidate): bool
    {
        return strtolower($candidate->getRole()?->getName() ?? '') === 'candidate'
            && $application->getUser()?->getId() === $candidate->getId()
            && strtolower($application->getCurrentStatus()) === 'pending';
    }
}
