<?php

namespace App\Form;

use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => '2',
                    'maxlength' => '180',
                    'placeholder' => 'example@musicoria.fr'
                ],
                'label' => 'Adresse email',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\Length(['min'=> 2, 'max' => 180]),
                    new Assert\Email(),
                    new Assert\NotBlank()
                ]
            ])

            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'label_attr' => [
                        'class' => 'form-label'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'label_attr' => [
                        'class' => 'form-label'
                    ],
                    'attr' => [
                        'class' => 'form-control',
                    ],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas !'
            ])

            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => '2',
                    'maxlength' => '50',
                    'placeholder' => 'Entrez votre nom'
                ],
                'label' => 'Nom',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\Length(['min'=> 2, 'max' => 50]),
                    new Assert\NotBlank()
                ]
            ])

            ->add('prenom', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => '2',
                    'maxlength' => '50',
                    'placeholder' => 'Entrez votre prenom'
                ],
                'label' => 'Prenom',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\Length(['min'=> 2, 'max' => 50]),
                    new Assert\NotBlank()
                ]
            ])

            ->add('telephone', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => '10',
                    'maxlength' => '20',
                    'placeholder' => 'Entrez votre numéro de téléphone'
                ],
                'label' => 'Téléphone',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\Length(['min'=> 2, 'max' => 50]),
                    new Assert\NotBlank(),
                    new Assert\Regex([
                        'pattern' => '/^\+?\d{10,15}$/', // Un format générique international
                        'message' => 'Veuillez entrer un numéro de téléphone valide.'
                    ])
                ]
            ])

            ->add('adresse', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre adresse complète'
                ],
                'label' => 'Adresse',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min'=> 5, 'max' => 255])
                ]
            ])

            ->add('typeUtilisateur', ChoiceType::class, [
                'choices' => TypeUtilisateur::cases(), // Génère toutes les valeurs de l'enum
                'choice_value' => fn (?TypeUtilisateur $enum) => $enum?->value, // Valeur pour le stockage
                'choice_label' => fn (TypeUtilisateur $enum) => match ($enum) { // Labels pour les options
                    TypeUtilisateur::PARTICULIER => 'Particulier',
                    TypeUtilisateur::PROFESSIONNEL => 'Professionnel',
                },
                'expanded' => true,  // Affiche des boutons radio
                'multiple' => false, // Une seule sélection autorisée
                'label' => 'Type d\'utilisateur',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez sélectionner un type d\'utilisateur.'
                    ]),
                ],
            ])
            
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'form-control btn btn-dark',
                ],
                'label' => 'Créer un compte',
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}
