<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Panier;
use GuzzleHttp\Client;
use App\Entity\Adresse;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Categorie;
use App\Entity\Fournisseur;
use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use App\Entity\PanierProduit;
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
                ->setImage('https://picsum.photos/id/'.mt_rand(10,100).'/640/480?random')
                ->setSlug($this->faker->slug);

            $manager->persist($mainCategory);
            $categories[$mainCategoryName] = $mainCategory;

            // Créer les sous-catégories
            foreach ($subCategories as $subCategoryName) 
            {
                $subCategory = new Categorie();
                $subCategory->setLibelle($subCategoryName)
                    ->setImage('https://picsum.photos/id/'.mt_rand(10,100).'/640/480?random')
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
                        ->setImage('https://picsum.photos/id/'.mt_rand(10,400).'/640/480?random')
                        ->setSlug($slug)
                        ->setActive(mt_rand(0, 1) == 1);
                    $manager->persist($produit);
                }
            }
        }

        // UTILISATEURS
        for ($u = 0; $u < 10; $u++) {
            $user = new Utilisateur();

            // Création d'une nouvelle adresse
            $adresse = new Adresse();
            $adresse->setLigne1($this->faker->streetAddress())
                    ->setVille($this->faker->city())
                    ->setCodePostal($this->faker->postcode())
                    ->setPays($this->faker->country());

            // Générer le nom et le prénom
            $nom = $this->faker->lastName();
            $prenom = $this->faker->firstName();
            $nomPrenom = $nom . ' ' . $prenom;

            // Générer l'email en incluant le nom et le prénom
            $emailUtilisateur = strtolower(str_replace(' ', '.', $nomPrenom)) . '@gmail.com';

            // Génération des données
            $user->setEmail($emailUtilisateur)
                ->setTypeUtilisateur($this->faker->randomElement([TypeUtilisateur::PARTICULIER, TypeUtilisateur::PROFESSIONNEL]))
                ->setNom($nom)
                ->setPrenom($prenom)
                ->setRoles(['ROLE_CLIENT'])
                ->setTelephone($this->faker->phoneNumber())
                ->setCoefficient($this->faker->randomFloat(2, 1, 5))
                ->addAdresse($adresse)
                ->setVerified($this->faker->boolean())
                ->setPlainPassword('password');

            $manager->persist($user);
        }

        $user = new Utilisateur();  
        $adresse = new Adresse();
        $adresse->setLigne1($this->faker->streetAddress())
                ->setVille($this->faker->city())
                ->setCodePostal($this->faker->postcode())
                ->setPays($this->faker->country());

        // Génération des données pour l'admin
        $user->setEmail('admin@musicoria.fr')
            ->setTypeUtilisateur($this->faker->randomElement([TypeUtilisateur::PARTICULIER, TypeUtilisateur::PROFESSIONNEL]))
            ->setNom('admin')
            ->setPrenom('admin')
            ->setRoles(['ROLE_ADMIN'])
            ->setTelephone($this->faker->phoneNumber())
            ->setCoefficient($this->faker->randomFloat(2, 1, 5))
            ->addAdresse($adresse)
            ->setVerified(true)
            ->setPlainPassword('admin');

        $manager->persist($user);

        $manager->flush();
        
        // COMMANDES
        for ($c = 0; $c < 20; $c++) {
            $commande = new Commande();
            
            // Sélection aléatoire d'un utilisateur existant
            $utilisateurs = $manager->getRepository(Utilisateur::class)->findAll();
            if ($utilisateurs) {
                $utilisateur = $utilisateurs[array_rand($utilisateurs)];
            }
            
            // Sélection aléatoire d'une adresse de l'utilisateur
            $adresse = $utilisateur->getAdresses()->first();
            
            // Générer un panier
            $panier = new Panier();
            $panier->setUtilisateur($utilisateur)
                   ->setCreatedAt(new \DateTimeImmutable())
                   ->setUpdatedAt(new \DateTimeImmutable())
                   ->setStatus($this->faker->randomElement(['en cours', 'validé', 'expédié']));
            
            $totalHT = 0;
            $totalTTC = 0;
            
            for ($i = 0; $i < rand(1, 10); $i++) {
                $produits = $manager->getRepository(Produit::class)->findAll();
                if ($produits) {
                    $produit = $produits[array_rand($produits)];
                    $quantite = rand(1, 4);
                    
                    $panierProduit = new PanierProduit();
                    $panierProduit->setPanier($panier)
                                ->setProduit($produit)
                                ->setQuantite($quantite)
                                ->setPrix($produit->getPrixVente())
                                ->setAddedAt(new \DateTimeImmutable());

                    $panier->addProduit($panierProduit);
                    $totalHT += $produit->getPrixVente() * $quantite;

                    $manager->persist($panierProduit);
                }

            }
            
            // Ajout des taxes
            $totalTTC = $totalHT * 1.2;
            
            $commande->setUtilisateur($utilisateur)
                    ->setAdresse($adresse)
                    ->setPanier($panier)
                    ->setPrixHT($totalHT)
                    ->setPrixTTC($totalTTC)
                    ->setStatus($this->faker->randomElement([
                        StatutCommande::EN_ATTENTE,
                        StatutCommande::VALIDEE,
                        StatutCommande::LIVREE
                    ]))
                    ->setCreatedAt(new \DateTimeImmutable())
                    ->setReference(strtoupper($this->faker->unique()->bothify('CMD-#####')))
                    ->setInvoicePdfUrl('https://example.com/facture/' . uniqid() . '.pdf');
            
            $manager->persist($panier);
            $manager->persist($commande);
        }
        
        $manager->flush();
    }


}
