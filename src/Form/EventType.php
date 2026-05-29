<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label'       => 'Event Title',
                'attr'        => ['placeholder' => 'e.g., Culture Night'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an event title.']),
                ],
            ])
            ->add('tags', TextType::class, [
                'label'    => 'Tags',
                'attr'     => ['placeholder' => 'Cultural, Festival, Music'],
                'required' => false,
            ])
            ->add('date', DateType::class, [
                'label'       => 'Event Date',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Please select an event date.']),
                ],
            ])
            ->add('startTime', TimeType::class, [
                'label'       => 'Start Time',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Please set the start time.']),
                ],
            ])
            ->add('endTime', TimeType::class, [
                'label'       => 'End Time',
                'widget'      => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Please set the end time.']),
                ],
            ])
            ->add('places', IntegerType::class, [
                'label'       => 'Available Places',
                'attr'        => ['placeholder' => '120', 'min' => 1],
                'constraints' => [
                    new NotBlank(['message' => 'Please specify the number of available places.']),
                    new Positive(['message' => 'Number of places must be positive.']),
                ],
            ])
            ->add('location', TextType::class, [
                'label'       => 'Location',
                'attr'        => ['placeholder' => 'Main Auditorium'],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a location.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label'       => 'Description',
                'attr'        => [
                    'rows'        => 5,
                    'placeholder' => 'Describe the event format, speakers, and what attendees should expect.',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a description.']),
                ],
            ])
            ->add('cover', FileType::class, [
                'label'    => 'Cover Photo / Poster',
                'mapped'   => false,
                'required' => false,
                'attr'     => ['accept' => 'image/*'],
                'constraints' => [
                    new File([
                        'maxSize'          => '5M',
                        'mimeTypes'        => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Please upload a valid image (JPG, PNG or WebP).',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'event',
        ]);
    }
}
