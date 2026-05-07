<?php

namespace App\Onboarding;

use App\Entity\Users;
use Symfony\Component\Validator\Constraints as Assert;

final class OnboardingPlanTemplateSelection
{
    #[Assert\NotBlank(message: 'Please choose an onboarding template.')]
    private ?string $templateKey = null;

    #[Assert\NotNull(message: 'Please choose a user for this onboarding plan.')]
    private ?Users $user = null;

    #[Assert\GreaterThanOrEqual(value: 'today', message: 'The deadline cannot be in the past.')]
    private ?\DateTimeInterface $deadline = null;

    public function getTemplateKey(): ?string
    {
        return $this->templateKey;
    }

    public function setTemplateKey(?string $templateKey): self
    {
        $this->templateKey = null !== $templateKey ? trim($templateKey) : null;

        return $this;
    }

    public function getUser(): ?Users
    {
        return $this->user;
    }

    public function setUser(?Users $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(?\DateTimeInterface $deadline): self
    {
        $this->deadline = $deadline;

        return $this;
    }
}
