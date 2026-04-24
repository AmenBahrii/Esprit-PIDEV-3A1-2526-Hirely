<?php

namespace App\Form;

use App\Entity\Application;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter')
            ->add('resumeFile', FileType::class, [
                'required' => false,
                'mapped' => true,
            ])
            ->add('expectedSalary')
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
                    'Pending' => 'pending',
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
