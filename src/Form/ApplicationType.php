<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Joboffer;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            
            ->add('applicationDate')
            ->add('coverLetter')
            ->add('currentStatus')
            ->add('resumePath')
            ->add('lastUpdateDate')
            ->add('expectedSalary')
            ->add('availabilityDate')
            ->add('phone')
            ->add('email')
            ->add('experienceYears')
            ->add('portfolioUrl')
            ->add('score')
            ->add('reviewNote')
            
            ->add('jobOffer', EntityType::class, [
                'class' => Joboffer::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
        ]);
    }
}
