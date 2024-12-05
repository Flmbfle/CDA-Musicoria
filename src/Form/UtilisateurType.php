<?php

namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Response;

class UtilisateurType extends AbstractType
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

            ->add('plainPassword', PasswordType::class, [
                'label' => 'Mot de passe',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'attr' => [
                    'class' => 'form-control',
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
