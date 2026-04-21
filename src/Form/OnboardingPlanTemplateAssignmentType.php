<?php

namespace App\Form;

use App\Entity\User;
use App\Onboarding\OnboardingPlanTemplateSelection;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OnboardingPlanTemplateAssignmentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('templateKey', ChoiceType::class, [
                'label' => 'Template',
                'placeholder' => false,
                'choices' => $options['template_choices'],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => static function (User $user): string {
                    return trim($user->getFirstName() . ' ' . $user->getLastName());
                },
                'label' => 'User',
                'placeholder' => 'Select a user',
            ])
            ->add('deadline', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Deadline',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OnboardingPlanTemplateSelection::class,
            'template_choices' => [],
        ]);

        $resolver->setAllowedTypes('template_choices', 'array');
    }
}
