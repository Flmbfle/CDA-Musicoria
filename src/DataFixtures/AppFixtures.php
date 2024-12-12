<?php

namespace App\DataFixtures;

use Faker\Factory;
use GuzzleHttp\Client;
use App\Entity\Produit;
use App\Entity\Categorie;
use App\Entity\Fournisseur;
use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    private $faker;

    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    // Fonction pour générer un slug propre
    public function slug(string $string): string
    {
        // Convertir le texte en minuscule
        $slug = strtolower($string);

        // Remplacer les accents par leur version sans accent
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

        // Remplacer les espaces et autres séparateurs par des tirets
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Supprimer les tirets en début et fin de chaîne
        $slug = trim($slug, '-');

        return $slug;
    }

    public function load(ObjectManager $manager): void
    {
        // Liste des catégories principales et leurs sous-catégories
        $categoriesData = [
            'Cordes' => ['Guitare', 'Violon', 'Violoncelle', 'Harpe', 'Banjo'],
            'Vent' => ['Flûte', 'Trompette', 'Clarinette', 'Saxophone', 'Harmonica'],
            'Percussion' => ['Djembé', 'Tambour', 'Xylophone', 'Cymbales', 'Triangle'],
            'Clavier' => ['Piano', 'Orgue', 'Synthétiseur', 'Clavecin'],
            'Électronique' => ['Theremin', 'Drum machine', 'Sampler'],
        ];

        $categories = [];

        // CATEGORIES PRINCIPALES ET SOUS-CATÉGORIES
        foreach ($categoriesData as $mainCategoryName => $subCategories) 
        {
            // Créer la catégorie principale
            $mainCategory = new Categorie();
            $mainCategory->setLibelle($mainCategoryName)
                ->setImage('https://picsum.photos/640/480?random')
                ->setSlug($this->faker->slug);

            $manager->persist($mainCategory);
            $categories[$mainCategoryName] = $mainCategory;

            // Créer les sous-catégories
            foreach ($subCategories as $subCategoryName) 
            {
                $subCategory = new Categorie();
                $subCategory->setLibelle($subCategoryName)
                    ->setImage('https://picsum.photos/640/480?random')
                    ->setSlug($this->faker->slug)
                    ->setParent($mainCategory); // Associer à la catégorie principale

                $manager->persist($subCategory);
                $categories[$subCategoryName] = $subCategory;
            }
        }

        // FOURNISSEURS
        $fournisseurs = [];
        for ($f = 0; $f < 10; $f++) 
        {
            $fournisseur = new Fournisseur();
            // Générer le nom du fournisseur
            $nomFournisseur = $this->faker->company(); 
            // Générer l'email en incluant le nom du fournisseur
            $emailFournisseur = strtolower(str_replace(' ', '.', $nomFournisseur)) . '@fournis.com';

            $fournisseur->setNom($nomFournisseur)
                ->setEmail($emailFournisseur)
                ->setPhone($this->faker->phoneNumber)
                ->setAdresse($this->faker->address);
            $manager->persist($fournisseur);
            $fournisseurs[] = $fournisseur;
        }

        // PRODUITS
        foreach ($categoriesData as $mainCategoryName => $subCategories) 
        {
            foreach ($subCategories as $instrument) 
            {
                // Ajout d'un compteur ou d'un identifiant unique
                for ($i = 1; $i <= 3; $i++) { // 3 produits par instrument, par exemple
                    $uniqueName = $instrument . ' ' . $this->faker->unique()->word;

                     // Générer le slug à partir du nom
                    $slug = $this->slug($uniqueName); // Utilisation de la méthode pour générer le slug

                    $produit = new Produit();
                    $produit->setLibelle($uniqueName)
                        ->setPrixAchat($pa = mt_rand(10, 500))
                        ->setPrixVente(mt_rand($pa + 10, $pa + 500))
                        ->setStock(mt_rand(1, 100))
                        ->setFournisseur($fournisseurs[array_rand($fournisseurs)]) // Fournisseur aléatoire
                        ->setCategorie($categories[$instrument]) // Associer à la sous-catégorie
                        ->setDescription($this->faker->text(200))
                        ->setImage('https://picsum.photos/640/480?random')
                        ->setSlug($slug)
                        ->setActive(mt_rand(0, 1) == 1);
                    $manager->persist($produit);
                }
            }
        }

        // UTILISATEURS
        for ($u = 0; $u < 10; $u++) {
            $user = new Utilisateur();

            // Génération des données
            $user->setEmail($this->faker->unique()->email())
                ->setTypeUtilisateur($this->faker->randomElement([TypeUtilisateur::PARTICULIER, TypeUtilisateur::PROFESSIONNEL]))
                ->setNom($this->faker->lastName())
                ->setPrenom($this->faker->firstName())
                ->setRoles(['ROLE_CLIENT'])
                ->setTelephone($this->faker->phoneNumber())
                ->setCoefficient($this->faker->randomFloat(2, 1, 5))
                ->setAdresse($this->faker->address())
                ->setVerified($this->faker->boolean())
                ->setPlainPassword('password');

            $manager->persist($user);
        }

        $user = new Utilisateur();

        // Génération des données pour l'admin
        $user->setEmail('admin@musicoria.fr')
            ->setTypeUtilisateur($this->faker->randomElement([TypeUtilisateur::PARTICULIER, TypeUtilisateur::PROFESSIONNEL]))
            ->setNom('admin')
            ->setPrenom('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setTelephone($this->faker->phoneNumber())
            ->setCoefficient($this->faker->randomFloat(2, 1, 5))
            ->setAdresse($this->faker->address())
            ->setVerified(true)
            ->setPlainPassword('admin');

        $manager->persist($user);

        $manager->flush();
    }
}
