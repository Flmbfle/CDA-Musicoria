<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use App\Entity\Fournisseur;
use App\Entity\Produit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;

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
        for ($p = 0; $p < 10; $p++) {
            $categorie = new Categorie();
            $categorie->setLibelle('Categorie ' . $this->faker->word)
                      ->setImage($this->faker->imageUrl(640, 480, 'music', true)) // Image d'une catégorie
                      ->setSlug($this->faker->slug);
            $manager->persist($categorie);
        }

        for ($p = 0; $p < 10; $p++) {
            $fournisseur = new Fournisseur();
            $fournisseur->setNom('Fournisseur ' . $this->faker->word)
                        ->setEmail($this->faker->email)
                        ->setPhone($this->faker->phoneNumber)
                        ->setAdresse($this->faker->address);
            $manager->persist($fournisseur);
        }

        for ($p = 0; $p < 25; $p++) {
            $produit = new Produit();
            $produit->setLibelle($this->faker->word)
                    ->setPrixAchat($pa = mt_rand(0, 1000))
                    ->setPrixVente(mt_rand($pa, 1000))
                    ->setStock(mt_rand(0, 50))
                    ->setFournisseur($fournisseur)
                    ->setCategorie($categorie)
                    ->setDescription($this->faker->sentence)
                    ->setImage($this->faker->imageUrl(640, 480, 'musical instrument', true)) // Image pour le produit
                    ->setSlug($this->faker->slug)
                    ->setActive(true);
            $manager->persist($produit);
        }
    
        $manager->flush();
    }
}
