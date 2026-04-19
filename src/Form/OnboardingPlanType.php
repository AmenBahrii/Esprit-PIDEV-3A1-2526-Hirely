<?php

namespace App\Form;

use App\Entity\Onboardingplan;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnboardingPlanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('user', EntityType::class, [
            'class' => User::class,
            'choice_label' => function (User $user) {
                return $user->getFirstName() . ' ' . $user->getLastName();
            },
            'label' => 'User',
            'placeholder' => 'Select a user',
        ]);

        $builder->add('deadline', DateType::class, [
            'widget' => 'single_text',
            'required' => false,
            'label' => 'Deadline',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Onboardingplan::class,
            'validation_groups' => ['Default', 'full_edit'],
        ]);
    }
}
