<?php

namespace App\Form;

use App\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter')
            ->add('resumeFile', FileType::class, [
                'required' => false,
                'mapped' => false,
            ])
            ->add('expectedSalary', NumberType::class, [
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
            ->add('availabilityDate', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'attr' => [
                    'class' => 'form-input',
                ],
            ])
            ->add('phone')
            ->add('email')
            ->add('experienceYears')
            ->add('portfolioUrl');

        if ($options['admin_mode']) {
            $builder->add('currentStatus', ChoiceType::class, [
                'choices' => [
                    'Pending' => 'Pending',
                    'Reviewed' => 'Reviewed',
                    'Accepted' => 'Accepted',
                    'Rejected' => 'Rejected',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
            'admin_mode' => false,
        ]);
    }
}
