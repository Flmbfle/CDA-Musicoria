<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use App\Entity\Role;
use App\Entity\Utilisateur;
use App\Enum\TypeUtilisateur;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    /**
     * @var Generator
     */
    private Generator $faker;
    
    public function __construct()
    {
        $this->faker = Factory::create('fr_FR');
    }

    public function load(ObjectManager $manager): void
    {
        $categories = [];
    
        // CATEGORIES
        for ($c = 0; $c < 10; $c++) 
        {
            $categorie = new Categorie();
            $categorie->setLibelle('Categorie ' . $this->faker->word)
                ->setImage($this->faker->imageUrl(640, 480, 'music', true))
                ->setSlug($this->faker->slug);
    
            // Assigner une catégorie parente aléatoire
            if (!empty($categories) && mt_rand(1, 100) <= 80) { // 80% de chances d'avoir un parent
                $possibleParent = $categories[array_rand($categories)];
                if (!$possibleParent->getParent()) { // Vérifier que le parent potentiel n'a pas de parent
                    $categorie->setParent($possibleParent);
                }
            }
    
            $manager->persist($categorie);
            $categories[] = $categorie; // Stocker les catégories pour références futures
        }
    
        // FOURNISSEURS
        $fournisseurs = [];
        for ($f = 0; $f < 10; $f++) 
        {
            $fournisseur = new Fournisseur();
            $fournisseur->setNom('Fournisseur ' . $this->faker->word)
                ->setEmail($this->faker->email)
                ->setPhone($this->faker->phoneNumber)
                ->setAdresse($this->faker->address);
            $manager->persist($fournisseur);
            $fournisseurs[] = $fournisseur;
        }
    
        // PRODUITS
        for ($p = 0; $p < 60; $p++) 
        {
            $produit = new Produit();
            $produit->setLibelle($this->faker->word)
                ->setPrixAchat($pa = mt_rand(10, 500))
                ->setPrixVente(mt_rand($pa + 10, $pa + 500))
                ->setStock(mt_rand(1, 100))
                ->setFournisseur($fournisseurs[array_rand($fournisseurs)]) // Fournisseur aléatoire
                ->setCategorie($categories[array_rand($categories)]) // Catégorie aléatoire
                ->setDescription($this->faker->text(200))
                ->setImage($this->faker->imageUrl(640, 480, 'musical instrument', true))
                ->setSlug($this->faker->slug)
                ->setActive(mt_rand(0, 1) == 1);
            $manager->persist($produit);
        }
    
        // UTILISATEUR
        for ($u = 0; $u < 10; $u++) {
            $user = new Utilisateur();

            // Génération des données
            $user->setEmail($this->faker->unique()->email())
                ->setTypeUtilisateur([TypeUtilisateur::PARTICULIER])
                ->setNom($this->faker->lastName())
                ->setPrenom($this->faker->firstName())
                ->setRoles(['ROLE_CLIENT'])
                ->setTelephone($this->faker->phoneNumber())
                ->setCoefficient($this->faker->randomFloat(2, 1, 5)) // Coefficient entre 1.00 et 5.00
                ->setAdresse($this->faker->address())
                ->setVerified($this->faker->boolean())
                ->setPlainPassword('password');

            $manager->persist($user);
        }

        $user = new Utilisateur();

        // Génération des données
        $user->setEmail('admin@musicoria.fr')
            ->setTypeUtilisateur([TypeUtilisateur::PROFESSIONNEL])
            ->setNom('admin')
            ->setPrenom('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setTelephone($this->faker->phoneNumber())
            ->setCoefficient($this->faker->randomFloat(2, 1, 5)) // Coefficient entre 1.00 et 5.00
            ->setAdresse($this->faker->address())
            ->setVerified(true)
            ->setPlainPassword('admin');

        $manager->persist($user);
        
        $manager->flush();
    }
    
}
