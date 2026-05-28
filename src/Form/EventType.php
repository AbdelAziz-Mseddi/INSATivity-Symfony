<?php

namespace App\Form;

use App\Entity\Club;
use App\Entity\Event;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Event creation and editing form.
 *
 * Maps to App\Entity\Event (to be created by the Doctrine teammate).
 * The entity should have properties: title, club (ManyToOne → Club),
 * image, eventDate, eventTime, location, description, participants,
 * maxParticipants, featured, tags.
 *
 * Usage in controller:
 *   $form = $this->createForm(EventType::class, $event);
 *   $form = $this->createForm(EventType::class, $event, ['is_edit' => true]);
 */
class EventType extends AbstractType
{
    /**
     * Predefined tags for event categorization.
     * Tags stored as a VARCHAR(15)[] array in the database.
     */
    private const AVAILABLE_TAGS = [
        'Workshop'      => 'workshop',
        'Competition'   => 'competition',
        'Conference'    => 'conference',
        'Hackathon'     => 'hackathon',
        'Training'      => 'training',
        'Social'        => 'social',
        'Sports'        => 'sports',
        'Cultural'      => 'cultural',
        'Music'         => 'music',
        'Networking'    => 'networking',
        'Career'        => 'career',
        'Charity'       => 'charity',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('title', TextType::class, [
                'label' => 'Event Title',
                'attr' => [
                    'placeholder' => 'Enter event title',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter the event title.']),
                    new Length([
                        'max' => 150,
                        'maxMessage' => 'Title cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('club', EntityType::class, [
                'class' => Club::class,
                'choice_label' => 'name',
                'label' => 'Organizing Club',
                'placeholder' => 'Select a club',
                'constraints' => [
                    new NotNull(['message' => 'Please select the organizing club.']),
                ],
            ])
            ->add('image', FileType::class, [
                'label' => 'Event Image',
                'mapped' => false, // Handled manually in the controller (file upload)
                'required' => !$isEdit, // Required only on creation
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or WebP).',
                        'maxSizeMessage' => 'Image is too large ({{ size }} {{ suffix }}). Maximum allowed: {{ limit }} {{ suffix }}.',
                    ]),
                ],
            ])
            ->add('eventDate', DateType::class, [
                'label' => 'Event Date',
                'widget' => 'single_text', // Renders as <input type="date">
                'constraints' => [
                    new NotBlank(['message' => 'Please select the event date.']),
                ],
            ])
            ->add('eventTime', TimeType::class, [
                'label' => 'Event Time',
                'widget' => 'single_text', // Renders as <input type="time">
                'constraints' => [
                    new NotBlank(['message' => 'Please select the event time.']),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'attr' => [
                    'placeholder' => 'e.g. Auditorium, Reading Room, Online...',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter the event location.']),
                    new Length([
                        'max' => 200,
                        'maxMessage' => 'Location cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Describe the event...',
                    'rows' => 6,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter an event description.']),
                    new Length([
                        'max' => 5000,
                        'maxMessage' => 'Description cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('participants', IntegerType::class, [
                'label' => 'Current Participants',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0',
                ],
                'constraints' => [
                    new NotNull(['message' => 'Please enter the number of participants.']),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Participants cannot be negative.',
                    ]),
                ],
            ])
            ->add('maxParticipants', IntegerType::class, [
                'label' => 'Max Participants',
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0 = unlimited',
                ],
                'help' => 'Set to 0 for unlimited capacity.',
                'constraints' => [
                    new NotNull(['message' => 'Please enter the max participants.']),
                    new GreaterThanOrEqual([
                        'value' => 0,
                        'message' => 'Max participants cannot be negative.',
                    ]),
                ],
            ])
            ->add('featured', CheckboxType::class, [
                'label' => 'Featured on homepage',
                'required' => false,
            ])
            ->add('tags', ChoiceType::class, [
                'label' => 'Tags',
                'choices' => self::AVAILABLE_TAGS,
                'multiple' => true,
                'expanded' => true, // Renders as checkboxes instead of a multi-select
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'event',
            'is_edit' => false, // Set to true when editing an existing event
        ]);

        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
