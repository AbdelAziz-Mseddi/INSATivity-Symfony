<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label'       => 'Full Name',
                'attr'        => ['placeholder' => 'Full Name'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your full name.']),
                    new Length(['min' => 2, 'max' => 100]),
                ],
            ])
            ->add('username', TextType::class, [
                'label'       => 'Username',
                'attr'        => ['placeholder' => 'Username'],
                'constraints' => [
                    new NotBlank(['message' => 'Please choose a username.']),
                    new Length(['min' => 3, 'max' => 50]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label'       => 'University Email',
                'attr'        => ['placeholder' => 'University Email'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your university email.']),
                ],
            ])
            ->add('major', ChoiceType::class, [
                'label'       => 'Major',
                'placeholder' => 'Select Major',
                'choices'     => [
                    'MPI' => 'MPI',
                    'CBA' => 'CBA',
                    'GL'  => 'GL',
                    'RT'  => 'RT',
                    'IIA' => 'IIA',
                    'IMI' => 'IMI',
                    'BIO' => 'BIO',
                    'CH'  => 'CH',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please select your major.']),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label'       => 'Password',
                'attr'        => ['placeholder' => 'Password'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a password.']),
                    new Length(['min' => 6, 'minMessage' => 'Password must be at least {{ limit }} characters.']),
                ],
            ])
            ->add('confirmPassword', PasswordType::class, [
                'label'    => 'Confirm Password',
                'attr'     => ['placeholder' => 'Confirm Password'],
                'mapped'   => false,
                'constraints' => [
                    new NotBlank(['message' => 'Please confirm your password.']),
                ],
            ])
            ->add('acceptTerms', CheckboxType::class, [
                'label'    => 'I accept the terms & data privacy policy',
                'mapped'   => false,
                'constraints' => [
                    new NotBlank(['message' => 'You must accept the terms.']),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'registration',
        ]);
    }
}
