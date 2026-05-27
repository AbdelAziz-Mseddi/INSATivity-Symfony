<?php

namespace App\Form;

use App\Entity\Club;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Club creation and editing form.
 *
 * Maps to App\Entity\Club (to be created by the Doctrine teammate).
 * The entity should have properties: name, category, logo, banner, description.
 */
class ClubType extends AbstractType
{
    /**
     * Club categories matching the existing seed data.
     */
    private const CATEGORIES = [
        'Technology'      => 'Technology',
        'Arts'            => 'Arts',
        'Entrepreneurship' => 'Entrepreneurship',
        'Social'          => 'Social',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Club Name',
                'attr' => [
                    'placeholder' => 'Enter club name',
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter the club name.']),
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Club name cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'placeholder' => 'Select a category',
                'choices' => self::CATEGORIES,
                'constraints' => [
                    new NotBlank(['message' => 'Please select a category.']),
                ],
            ])
            ->add('logo', FileType::class, [
                'label' => 'Club Logo',
                'mapped' => false, // Handled manually in the controller (file upload)
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/svg+xml',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, WebP, or SVG).',
                        'maxSizeMessage' => 'Logo file is too large ({{ size }} {{ suffix }}). Maximum allowed: {{ limit }} {{ suffix }}.',
                    ]),
                ],
            ])
            ->add('banner', FileType::class, [
                'label' => 'Club Banner',
                'mapped' => false, // Handled manually in the controller (file upload)
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Please upload a valid image (JPEG, PNG, or WebP).',
                        'maxSizeMessage' => 'Banner file is too large ({{ size }} {{ suffix }}). Maximum allowed: {{ limit }} {{ suffix }}.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'placeholder' => 'Describe the club and its mission...',
                    'rows' => 5,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a description.']),
                    new Length([
                        'max' => 2000,
                        'maxMessage' => 'Description cannot exceed {{ limit }} characters.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Club::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'club',
        ]);
    }
}
