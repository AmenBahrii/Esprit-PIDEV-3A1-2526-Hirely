<?php

namespace App\Form;

use App\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('score', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-input',
                    'min' => 0,
                    'max' => 100,
                    'step' => '0.01',
                    'placeholder' => 'Enter a score out of 100',
                ],
            ])
            ->add('reviewNote', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-input',
                    'rows' => 5,
                    'placeholder' => 'Add your recruiter review note',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}
