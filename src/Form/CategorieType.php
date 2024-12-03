<?php

namespace App\Form;

use App\Entity\Categorie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Doctrine\ORM\EntityRepository;

class CategorieType extends AbstractType
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
                'data_class' => null,
            ])
            ->add('slug', TextType::class, [
                'attr' => [
                    'class' => 'form-control', // Ajout de la classe Bootstrap
                ]
            ])
            ->add('parent',  EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'libelle',
                'placeholder' => 'Selectionnez un parent',
                'query_builder' => function (EntityRepository $er) use ($options) {
                    // Récupérer l'entité en cours de modification
                    if($options['data']->getId()!=null)
                    {
                    $currentCategory = $options['data'];

                    return $er->createQueryBuilder('c')
                        ->where('c.parent IS NULL') // Exclure les catégories déjà parents
                        ->andWhere('c != :current') // Exclure la catégorie actuelle
                        ->setParameter('current', $currentCategory);
                }
             else if($options['data']->getId()==null)
        {
            return $er->createQueryBuilder('c')
                             ->where('c.parent IS NULL'); // Exclure les catégories déjà parents
                            // Exclure la catégorie actuelle
        }},
                'required' => false,
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
            'data_class' => Categorie::class,
        ]);
    }
}
