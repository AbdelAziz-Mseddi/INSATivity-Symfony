<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Student registration form.
 *
 * Maps to App\Entity\User (to be created by the Doctrine teammate).
 * The entity should have properties: fullName, username, email, major, password.
 */
class RegistrationType extends AbstractType
{
    /**
     * Available majors at INSAT.
     */
    private const MAJORS = [
        'MPI' => 'MPI',
        'CBA' => 'CBA',
        'GL'  => 'GL',
        'RT'  => 'RT',
        'IIA' => 'IIA',
        'IMI' => 'IMI',
        'BIO' => 'BIO',
        'CH'  => 'CH',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Full Name',
                'attr' => [
                    'placeholder' => 'Full Name',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your full name.']),
                    new Length([
                        'min' => 2,
                        'max' => 100,
                        'minMessage' => 'Your name must be at least {{ limit }} characters.',
                        'maxMessage' => 'Your name cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'Username',
                    'autocomplete' => 'username',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please choose a username.']),
                    new Length([
                        'min' => 3,
                        'max' => 30,
                        'minMessage' => 'Username must be at least {{ limit }} characters.',
                        'maxMessage' => 'Username cannot exceed {{ limit }} characters.',
                    ]),
                    new Regex([
                        'pattern' => '/^[a-zA-Z0-9._-]+$/',
                        'message' => 'Username can only contain letters, numbers, dots, hyphens, and underscores.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'University Email',
                'attr' => [
                    'placeholder' => 'University Email',
                ],
                'help' => 'Use your @insat.ucar.tn email address.',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your university email.']),
                    new Email(['message' => 'Please enter a valid email address.']),
                    new Regex([
                        'pattern' => '/@insat\.ucar\.tn$/i',
                        'message' => 'You must use your INSAT university email (@insat.ucar.tn).',
                    ]),
                ],
            ])
            ->add('major', ChoiceType::class, [
                'label' => 'Major',
                'placeholder' => 'Select Major',
                'choices' => self::MAJORS,
                'constraints' => [
                    new NotBlank(['message' => 'Please select your major.']),
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Password',
                    'attr' => [
                        'placeholder' => 'Password',
                        'autocomplete' => 'new-password',
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'Please enter a password.']),
                        new Length([
                            'min' => 6,
                            'max' => 255,
                            'minMessage' => 'Password must be at least {{ limit }} characters.',
                        ]),
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirm Password',
                    'attr' => [
                        'placeholder' => 'Confirm Password',
                        'autocomplete' => 'new-password',
                    ],
                ],
                'invalid_message' => 'The passwords do not match.',
            ])
            ->add('acceptTerms', CheckboxType::class, [
                'label' => 'I accept the terms & data privacy policy',
                'mapped' => false, // Not stored in the entity
                'constraints' => [
                    new IsTrue(['message' => 'You must accept the terms and privacy policy.']),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'registration',
        ]);
    }
}
