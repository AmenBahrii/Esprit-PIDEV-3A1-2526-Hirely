<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordConstraints = [
            new Callback(function ($value, ExecutionContextInterface $context): void {
                if ($value === null || $value === '') {
                    return;
                }

                if (mb_strlen((string) $value) < 6) {
                    $context->buildViolation('Password must be at least 6 characters')
                        ->addViolation();
                }
            }),
        ];

        if ($options['password_required']) {
            array_unshift($passwordConstraints, new NotBlank(message: 'Password is required'));
        }

        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => $options['password_required'],
                'empty_data' => '',
                'attr' => [
                    'autocomplete' => 'new-password',
                ],
                'constraints' => $passwordConstraints,
            ])
            ->add('profilePic');

        if ($options['is_admin']) {
            $builder
                ->add('role', EntityType::class, [
                    'class' => Role::class,
                    'choice_label' => 'name',
                ])
                ->add('status', ChoiceType::class, [
                    'choices' => [
                        'Active' => 'active',
                        'Inactive' => 'inactive',
                    ],
                ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
            'is_admin' => false,
            'password_required' => false,
        ]);
    }
}
