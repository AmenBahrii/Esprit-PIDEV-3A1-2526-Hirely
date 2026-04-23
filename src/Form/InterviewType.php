<?php

namespace App\Form;

use App\Entity\Interviews;
use App\Entity\Interview_types;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InterviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('interview_type_id', EntityType::class, [
                'class' => Interview_types::class,
                'choice_label' => 'type_name',
                'label' => 'Interview Type',
                'placeholder' => 'Select an interview type',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('scheduled_date', DateType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'label' => 'Scheduled Date',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('scheduled_time', TimeType::class, [
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime',
                'required' => false,
                'label' => 'Scheduled Time',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('duration_minutes', IntegerType::class, [
                'label' => 'Duration (minutes)',
                'attr' => ['class' => 'form-control', 'min' => 15],
            ])
            ->add('location', TextType::class, [
                'required' => false,
                'label' => 'Location',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g., Conference Room A or Virtual'],
            ])
            ->add('meeting_link', TextType::class, [
                'required' => false,
                'label' => 'Meeting Link',
                'attr' => ['class' => 'form-control', 'placeholder' => 'e.g., https://meet.google.com/...'],
            ])
            ->add('notes', TextareaType::class, [
                'required' => false,
                'label' => 'Notes',
                'attr' => ['class' => 'form-control', 'rows' => 4],
            ])
            ->add('interview_round', IntegerType::class, [
                'label' => 'Interview Round',
                'data' => 1,
                'attr' => ['class' => 'form-control', 'min' => 1],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Interviews::class,
        ]);
    }
}
