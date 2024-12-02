<?php

namespace App\Form;

use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Fournisseur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'attr' => [
                    'class' => 'form-control',
                    'minlength' => '2',
                    'maxlength' => '50',
                    'placeholder' => 'Entrez le libellé du produit'
                ],
                'label' => 'Libelle',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\Length(['min'=> 2, 'max' => 50]),
                    new Assert\NotBlank()
                ]
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control', // Ajout de la classe Bootstrap
                    'rows' => 4, // Définir une hauteur (nombre de lignes visibles)
                    'placeholder' => 'Entrez une description détaillée du produit...', // Ajouter un texte d'aide
                ],
                'label' => 'Description',
                'label_attr' => [
                    'class' => 'form-label', // Classe pour le label
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description ne peut pas être vide.']),
                    new Assert\Length([
                        'max' => 500,
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('slug')
            ->add('image', FileType::class, [
                'attr' => ['class' => 'form-control'],
                'label' => 'Image du produit',
                'constraints' => [
                    new Assert\Image([
                        'maxSize' => '2M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image valide (JPEG ou PNG).',
                    ]),
                ],
            ])
            ->add('stock', IntegerType::class, [
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\PositiveOrZero(),
                ],
            ])
            
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => 'Produit actif',
            ])
            ->add('prixAchat', MoneyType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Prix d\'achat',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Positive()
                ],
                'currency' => 'EUR',
            ])
            ->add('prixVente', MoneyType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'label' => 'Prix de vente',
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Positive()
                ],
                'currency' => 'EUR',
            ])
            ->add('fournisseur', EntityType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'class' => Fournisseur::class,
                'choice_label' => 'nom',
                'placeholder' => 'Sélectionnez un fournisseur',
            ])
            ->add('categorie', EntityType::class, [
                'attr' => [
                    'class' => 'form-control',
                ],
                'class' => Categorie::class,
                'label' => 'Catégorie',
                'choice_label' => 'libelle',
                'placeholder' => 'Sélectionnez une catégorie',
            ])
            ->add('submit', SubmitType::class, [
                'attr' => [
                    'class' => 'form-control btn btn-dark',
                ],
                'label' => 'Créer le produit',
            ]);
        
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
