<?php

namespace App\Form;

use App\Entity\Role;
use App\Entity\Users;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
class UsersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName')
            ->add('lastName')
            ->add('email')
            ->add('password', PasswordType::class, [
    'mapped' => true,
])
            ->add('role', EntityType::class, [
    'class' => Role::class,
    'choice_label' => 'name',
])
            ->add('profilePic')
            ->add('faceData')
            ->add('googleId')
            ->add('status', ChoiceType::class, [
    'choices' => [
        'Active' => 'active',
        'Inactive' => 'inactive',
    ],
])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Users::class,
        ]);
    }
}
