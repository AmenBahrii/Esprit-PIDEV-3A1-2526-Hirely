<?php

namespace App\Form;

use App\Entity\Joboffer;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
            ->add('salary')
            ->add('location')
            ->add('experienceRequired')
            ->add('publicationDate')
            ->add('status')
            
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Joboffer::class,
        ]);
    }
}
