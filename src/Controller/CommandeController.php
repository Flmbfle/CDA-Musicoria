<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use App\Entity\PanierProduit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CommandeController extends AbstractController
{
    #[Route('commande/ajout', 'commande.ajout')]
    public function creerCommande(RequestStack $requestStack, ProduitRepository $produitRepository, EntityManagerInterface $em)
    {
        // Récupérer le panier stocké
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        $panier = $user->getPanier();

        // Si le panier est vide, rediriger vers la page panier
        if ($panier === null || $panier->getProduits()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier');
        }
        
        // Créer une nouvelle commande
        $commande = new Commande();
        $commande->setUtilisateur($user);
        $commande->setCreatedAt(new \DateTimeImmutable());
        $commande->setStatus(StatutCommande::EN_ATTENTE);
        
        // Initialiser les totaux HT et TTC
        $totalHT = 0;
        $totalTTC = 0;
        
        foreach($panier->getProduits() as $produits)
        {
            $produit = $produits->getProduit();
            $quantite = $produits->getQuantite();
            $prixUnitaire = $produits->getPrix();

            // Calculer le prix total HT pour ce produit
            $totalProduitHT = $prixUnitaire * $quantite;
            
            // Ajouter au total HT de la commande
            $totalHT += $totalProduitHT;
            
            // Calculer le prix TTC pour ce produit (ajout de 20% de TVA)
            $totalProduitTTC = $totalProduitHT * 1.20;
            
            // Ajouter au total TTC de la commande
            $totalTTC += $totalProduitTTC;
        }
        
        // Affecter les totaux à la commande
        $commande->setPrixHT($totalHT);
        $commande->setPrixTTC($totalTTC);
        
        $commande->setPanier($panier);
        // dd($commande);

        // Sauvegarder la commande dans la base de données
        $em->persist($commande);
        // dd($commande);
        $em->flush();

        foreach ($panier->getProduits() as $produitPanier) {
            $em->remove($produitPanier);
        }
        
        $em->flush();

        // Rediriger vers la confirmation de commande ou une autre page
        $this->addFlash('success', 'Votre commande a bien été enregistrée !');
        return $this->redirectToRoute('commande.confirmation', ['id' => $commande->getId()]);
    }

    #[Route('commande/{id}/confirmation', 'commande.confirmation')]
    public function confirmation(Commande $commande)
    {
        return $this->render('commande/confirmation.html.twig', [
            'commande' => $commande,
        ]);
    }
}
