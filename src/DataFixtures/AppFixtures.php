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
        $categorie = new Categorie();
        $categorie->setLibelle('Categorie #1')
                ->setImage('')
                ->setSlug('');
        $manager->persist($categorie);

        $fournisseur = new Fournisseur();
        $fournisseur->setNom('Fournisseur #1')
                    ->setEmail('')
                    ->setPhone('')
                    ->setAdresse('');
        $manager->persist($fournisseur);

        for ($p = 0; $p < 50; $p++)
        {
            $produit = new Produit();
            $produit->setLibelle($this->faker->word)
                    ->setPrixAchat($pa = mt_rand(0, 1000))
                    ->setPrixVente(mt_rand($pa, 1000))
                    ->setStock(mt_rand(0, 50))
                    ->setfournisseur($fournisseur)
                    ->setcategorie($categorie)
                    ->setDescription('Lorem')
                    ->setImage('')
                    ->setSlug('')
                    ->setActive(true);
            $manager->persist($produit);
        }

        $manager->flush();
    }
}
