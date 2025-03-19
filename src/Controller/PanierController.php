<?php

namespace App\Controller;

use DateTimeZone;
use App\Entity\Panier;
use DateTimeImmutable;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\PanierType;
use App\Entity\Utilisateur;
use App\Entity\PanierProduit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PanierController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
        $em;
    }


    #[Route('/panier', 'panier')]
    public function index()
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        $panier = $user->getPanierActif();

        // Si l'utilisateur n'a pas de panier, retourner une vue avec un message approprié
        if (!$panier) {
            return $this->render('pages/panier/index.html.twig', [
                'data' => [],
                'total' => 0,
            ]);
        }
    
        // Récupérer les produits dans le panier
        $panierProduits = $panier->getProduits();

        // Initialiser le total TTC à zéro
        $totalTTC = 0;

        // Taux de TVA (exemple à 20%)
        $tauxTVA = 0.20;

        // Calculer le total TTC du panier
        foreach ($panierProduits as $panierProduit) {
            // Prix HT du produit
            $prixHT = $panierProduit->getProduit()->getPrixVente();

            // Calcul du prix TTC
            $prixTTC = $prixHT * (1 + $tauxTVA);

            // Ajouter le prix TTC pour la quantité
            $totalTTC += $prixTTC * $panierProduit->getQuantite(); // On multiplie par la quantité
        }

        // Passer les données à la vue
        return $this->render('pages/panier/index.html.twig', [
            'data' => $panierProduits,
            'total' => $totalTTC,  // Passer le total TTC à la vue
        ]);
    }


    #[Route('panier/ajout/{id}', 'panier.ajouter')]
    public function ajouter(Produit $produit)
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }
        $panier = $user->getPanierActif();
        $timezone = new DateTimeZone('Europe/Paris'); 
        // Si l'utilisateur n'a pas encore de panier, en créer un
        if (!$panier) {
            $panier = new Panier();
            $panier->setUtilisateur($user)
                ->setCreatedAt(new DateTimeImmutable('now', $timezone))
                ->setUpdatedAt(new DateTimeImmutable('now', $timezone));
    
            $this->em->persist($panier);
            $this->em->flush();
        }
    
        // Vérifier si le produit existe déjà dans le panier de cet utilisateur
        $panierProduit = $this->em->getRepository(PanierProduit::class)->findOneBy([
            'produit' => $produit,
            'panier' => $panier,
        ]);
    
        if ($panierProduit) {
            // Si le produit est déjà dans le panier, on augmente la quantité
            $panierProduit->setQuantite($panierProduit->getQuantite() + 1);
        } else {
            // Sinon, on ajoute le produit au panier
            $panierProduit = new PanierProduit();
            $panierProduit->setProduit($produit)
                ->setPanier($panier)
                ->setQuantite(1)
                ->setPrix($produit->getPrixVente())
                ->setAddedAt(new DateTimeImmutable('now', $timezone));

    
            $this->em->persist($panierProduit);
        }
        $panier->setUpdatedAt(new DateTimeImmutable('now', $timezone));
    
        $this->em->flush();
    
        return $this->redirectToRoute('panier');
    }
    

    #[Route('panier/reduire/{id}', 'panier.reduire')]
    public function reduire(Produit $produit)
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }
        $panier = $user->getPanierActif();  // Récupérer le panier de l'utilisateur
    
        $panierProduit = $this->em->getRepository(PanierProduit::class)->findOneBy([
            'produit' => $produit,
            'panier' => $panier,  // Assurez-vous de rechercher par 'panier' et non 'user'
        ]);
    
        if ($panierProduit) {
            if ($panierProduit->getQuantite() > 1) {
                $panierProduit->setQuantite($panierProduit->getQuantite() - 1);
            } else {
                $this->em->remove($panierProduit);
            }
    
            $this->em->flush();
        }
    
        return $this->redirectToRoute('panier');
    }
    

    #[Route('panier/supprimer/{id}', 'panier.supprimer')]
    public function supprimer(Produit $produit)
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }
        $panier = $user->getPanierActif();  // Récupérer le panier de l'utilisateur
    
        $panierProduit = $this->em->getRepository(PanierProduit::class)->findOneBy([
            'produit' => $produit,
            'panier' => $panier,  // Utiliser 'panier' ici aussi
        ]);
    
        if ($panierProduit) {
            $this->em->remove($panierProduit);
            $this->em->flush();
        }
    
        return $this->redirectToRoute('panier');
    }

    
    #[Route('panier/vider', 'panier.vider')]
    public function vider()
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        $panier = $user->getPanierActif();  // Récupérer le panier de l'utilisateur
    
        $panierProduits = $this->em->getRepository(PanierProduit::class)->findBy([
            'panier' => $panier,  // Utiliser 'panier' ici aussi
        ]);
    
        foreach ($panierProduits as $panierProduit) {
            $this->em->remove($panierProduit);
        }
    
        $this->em->flush();
    
        return $this->redirectToRoute('panier');
    }
    
}
