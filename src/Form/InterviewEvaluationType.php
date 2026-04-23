<?php

namespace App\Form;

use App\Entity\Interview_evaluations;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterviewEvaluationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('recommendation', ChoiceType::class, [
                'label' => 'Recommendation',
                'choices' => [
                    'Strong Yes' => 'strong_yes',
                    'Yes' => 'yes',
                    'Maybe' => 'maybe',
                    'No' => 'no',
                    'Strong No' => 'strong_no',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('hire_decision', ChoiceType::class, [
                'label' => 'Hire Decision',
                'choices' => [
                    'Hire' => 'hire',
                    'No Hire' => 'no_hire',
                    'Maybe' => 'maybe',
                    'Pending' => 'pending',
                ],
                'attr' => ['class' => 'form-control'],
            ])
            ->add('strengths', TextareaType::class, [
                'label' => 'Strengths',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'List candidate strengths'],
            ])
            ->add('weaknesses', TextareaType::class, [
                'label' => 'Weaknesses',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'List areas for improvement'],
            ])
            ->add('general_comments', TextareaType::class, [
                'label' => 'General Comments',
                'attr' => ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Additional comments'],
            ])
            ->add('next_steps', TextareaType::class, [
                'label' => 'Next Steps',
                'attr' => ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Recommended next steps'],
            ])
            ->add('is_draft', CheckboxType::class, [
                'label' => 'Save as Draft',
                'required' => false,
                'attr' => ['class' => 'form-check-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Interview_evaluations::class,
        ]);
    }
}
