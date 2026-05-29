<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('event', TextType::class, [
                'label'       => 'Event',
                'attr'        => ['placeholder' => 'Choose an event...'],
                'constraints' => [
                    new NotBlank(['message' => 'Please select an event to rate.']),
                ],
            ])
            ->add('rating', NumberType::class, [
                'label'  => 'Rating (0–5)',
                'scale'  => 1,
                'attr'   => [
                    'placeholder' => '0–5',
                    'min'         => 0,
                    'max'         => 5,
                    'step'        => 0.5,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please provide a rating.']),
                    new Range([
                        'min'        => 0,
                        'max'        => 5,
                        'minMessage' => 'Rating cannot be less than 0.',
                        'maxMessage' => 'Rating cannot be more than 5.',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'Your message (optional)',
                'required' => false,
                'attr'     => [
                    'rows'        => 4,
                    'placeholder' => 'Share more about your experience—what you liked, what could be better, or suggestions for next time.',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'feedback',
        ]);
    }
}
