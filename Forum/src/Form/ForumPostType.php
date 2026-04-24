<?php

namespace App\Form;

use App\Entity\ForumPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ForumPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Share a question, update, or discussion topic',
                    'maxlength' => 80,
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'empty_data' => '',
                'attr' => [
                    'rows' => 10,
                    'placeholder' => 'Write your post here...',
                    'maxlength' => 5000,
                ],
            ])
            ->add('tag', TextType::class, [
                'label' => 'Tag',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'placeholder' => '#General',
                    'maxlength' => 30,
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
                ->add('isLocked', CheckboxType::class, [
                    'label' => 'Locked',
                    'required' => false,
                ])
                ->add('moderationNote', TextareaType::class, [
                    'label' => 'Moderation note',
                    'required' => false,
                    'attr' => ['rows' => 5],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ForumPost::class,
            'is_admin' => false,
        ]);
    }
}


