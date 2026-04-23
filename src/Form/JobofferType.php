<?php

namespace App\Form;

use App\Entity\Joboffer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
class JobofferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            
            ->add('title')
            ->add('description')
            ->add('contractType', ChoiceType::class, [
    'choices' => [
        'CDI' => 'CDI',
        'CDD' => 'CDD',
        'Internship' => 'Internship',
        'Freelance' => 'Freelance',
    ],
    'placeholder' => 'Select contract type',
    'attr' => ['class' => 'form-input']
])
            ->add('salary', NumberType::class, [
                'required' => false,
                'scale' => 2,
                'input' => 'string',
                'html5' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-input',
                    'step' => '0.01',
                    'min' => 0,
                ],
            ])
            ->add('location')
            ->add('experienceRequired')
        
            ->add('status', ChoiceType::class, [
    'choices' => [
        'Open' => 'Open',
        'Closed' => 'Closed',
    ],
    'placeholder' => 'Select status',
    'attr' => ['class' => 'form-input']
])
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Joboffer::class,
        ]);
    }
}
