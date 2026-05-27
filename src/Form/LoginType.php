<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Login form — not bound to any entity.
 *
 * Symfony Security's authenticator handles the actual login logic;
 * this form only provides structure, labels, and basic validation
 * for the Twig template to render.
 */
class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Username',
                'attr' => [
                    'placeholder' => 'Username',
                    'autocomplete' => 'username',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your username.']),
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Password',
                'attr' => [
                    'placeholder' => 'Password',
                    'autocomplete' => 'current-password',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter your password.']),
                ],
            ])
            ->add('remember', CheckboxType::class, [
                'label' => 'Remember me',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No entity mapping — Security authenticator processes login
            'data_class' => null,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'authenticate',
        ]);
    }
}
