<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Produit;
use App\Entity\Commande;
use App\Form\PanierType;
use App\Entity\PanierProduit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;

class PanierController extends AbstractController
{
    public function __construct(private RequestStack $requestStack)
    {

    }


    #[Route('/panier', 'panier')]
    public function index(RequestStack $requestStack, ProduitRepository $produitRepository)
    {
        $panier = $requestStack->getSession()->get('panier', []);

        // On initialise des variables
        $data = [];
        $total = 0;
    
        foreach($panier as $id => $quantite) 
        {
            $produit = $produitRepository->find($id);

            $data[] = [
                'produit' => $produit,
                'quantite' => $quantite,
            ];

            $total += $produit->getPrixVente()* $quantite;
        }

        return $this->render('pages/panier/index.html.twig', compact('data', 'total'));
    }


    #[Route('panier/ajout/{id}', 'panier.ajouter')]
    public function ajouter(Produit $produit)
    {
        $session = $this->requestStack->getSession();

        // Recuperer l'id du produit
        $id = $produit->getId();

        $panier = $session->get('panier', []);

        // Ajout du produit dans le panier s'il n'y est pas encore , sinon on incrémente sa quantité
        if(empty($panier[$id]))
        {
            $panier[$id] = 1;
        } else {
            $panier[$id]++;
        }

        // Recuperer le panier existant
        $session->set('panier', $panier);

        // On redirige vers la page du panier
        return $this->redirectToRoute('panier');
    }

    #[Route('panier/reduire/{id}', 'panier.reduire')]
    public function reduire(Produit $produit)
    {
        $session = $this->requestStack->getSession();

        // Recuperer l'id du produit
        $id = $produit->getId();

        $panier = $session->get('panier', []);

        // On retire le produit du panier s'il n'y a qu'un exemplaire , sinon on dé-crémente sa quantité
        if(!empty($panier[$id])){
            if($panier[$id] > 1){
                $panier[$id]--;
            } else {
                unset($panier[$id]);
            }
        }
        // Recuperer le panier existant
        $session->set('panier', $panier);

        // On redirige vers la page du panier
        return $this->redirectToRoute('panier');
    }

    #[Route('panier/supprimer/{id}', 'panier.supprimer')]
    public function supprimer(Produit $produit)
    {
        $session = $this->requestStack->getSession();

        // Recuperer l'id du produit
        $id = $produit->getId();

        $panier = $session->get('panier', []);

        // On verifie si le panier est vide, si il ne l'est pas on le vide
        if(!empty($panier[$id])){
            unset($panier[$id]);
        }
    
        // Recuperer le panier existant
        $session->set('panier', $panier);

        // On redirige vers la page du panier
        return $this->redirectToRoute('panier');
    }


    #[Route('panier/vider', 'panier.vider')]
    public function vider()
    {
        $session = $this->requestStack->getSession();
        $session->remove('panier');

        return $this->redirectToRoute('panier');
    }
}
