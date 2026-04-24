<?php

namespace App\Form;

use App\Entity\ForumComment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForumCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => 'Comment',
            'empty_data' => '',
            'attr' => [
                'rows' => 4,
                'maxlength' => 1000,
                'placeholder' => 'Write a comment...',
            ],
        ]);

        if ($options['is_admin']) {
            $builder
                ->add('status', ChoiceType::class, [
                    'label' => 'Status',
                    'choices' => [
                        'Approved' => 'APPROVED',
                        'Pending' => 'PENDING',
                        'Rejected' => 'REJECTED',
                    ],
                ])
                ->add('isPinned', CheckboxType::class, [
                    'label' => 'Pinned',
                    'required' => false,
                ])
                ->add('moderationNote', TextareaType::class, [
                    'label' => 'Moderation note',
                    'required' => false,
                    'attr' => ['rows' => 4],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumComment::class,
            'is_admin' => false,
        ]);
    }
}


