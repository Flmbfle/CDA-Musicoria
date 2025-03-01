<?php

namespace App\Controller;

use DateTimeZone;
use Dompdf\Dompdf;
use Dompdf\Options;
use DateTimeImmutable;
use App\Entity\Adresse;
use App\Entity\Commande;
use App\Form\AdresseType;
use Ramsey\Uuid\Guid\Guid;
use App\Entity\Utilisateur;
use App\Service\PDFService;
use App\Enum\StatutCommande;
use App\Entity\PanierProduit;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;


class CommandeController extends AbstractController
{
    #[Route('commande/ajout', 'commande.ajout', requirements: ['type' => 'facturation|livraison'])]
    public function creerCommande(ProduitRepository $produitRepository, EntityManagerInterface $em)
    {
        $user = $this->getUser();

        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        $adresseLivraison = $em->getRepository(Adresse::class)->findOneBy([
            'utilisateur' => $user,
            'isFacturation' => false
        ]);
        
        if (!$adresseLivraison) {
            $this->addFlash('error', 'Veuillez ajouter une adresse de livraison avant de valider votre commande.');
            return $this->redirectToRoute('commande.adresse', ['type' => 'livraison']);
        }

        // Récupération de l'adresse de facturation via Stripe (plus tard)
        $adresseFacturation = null;
        
        // Récupérer le panier stocké
        $panier = $user->getPanierActif();

        // Si le panier est vide, rediriger vers la page panier
        if ($panier === null || $panier->getProduits()->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('panier');
        }
        $timezone = new DateTimeZone('Europe/Paris'); 

        // Créer une nouvelle commande
        $commande = new Commande();
        $commande->setUtilisateur($user)
            ->setCreatedAt(new DateTimeImmutable('now', $timezone))
            ->setStatus(StatutCommande::EN_ATTENTE);
            do {
                $reference = uniqid(); // ou utilisez UUID ici
                $commandeExistante = $em->getRepository(Commande::class)->findOneBy(['reference' => $reference]);
            } while ($commandeExistante !== null); // Continue à générer une nouvelle référence si elle existe déjà
            
            $commande->setReference($reference);
            
        
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
        $commande->setAdresse($adresseLivraison);
        // dd($commande);

        // Sauvegarder la commande dans la base de données
        $em->persist($commande);
        $em->flush();

        // Vider les produits du panier
        foreach ($panier->getProduits() as $produitPanier) {
            $em->remove($produitPanier);
        }
    
        // Mettre à jour le statut du panier pour indiquer qu'il a été utilisé
        $panier->setStatus(true); // Change le statut à 1 (validé, utilisé)
    
        // Sauvegarder l'état du panier mis à jour
        $em->persist($panier);
        $em->flush();
        // Rediriger vers la confirmation de commande ou une autre page
        $this->addFlash('success', 'Votre commande a bien été enregistrée !');
        return $this->redirectToRoute('commande.details', ['id' => $commande->getId()]);
    }

    #[Route('/commande/{id}', name: 'commande.details', requirements: ['id' => '\d+'])]
    public function afficherCommande(CommandeRepository $commandeRepository, int $id)
    {
        // Récupérer la commande en fonction de l'ID
        $commande = $commandeRepository->find($id);
    
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée.');
        }
    
        // Vérifiez que l'utilisateur est bien le propriétaire de la commande
        $user = $this->getUser();
        if ($commande->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande.');
        }

        $panier = $commande->getPanier();
        $statut = $commande->getStatus();

        // dd($statut);
        $panierProduits = $panier->getProduits();
        

        // Renvoyer la commande à une vue ou effectuer d'autres actions
        return $this->render('pages/commande/details.html.twig', [
            'commande' => $commande, 
            'statut' => $statut,
            'panierProduits' => $panierProduits,
        ]);
    }

    #[Route('/commande/historique', name: 'commande.historique')]
    public function historique(Request $request, CommandeRepository $commandeRepository, PaginatorInterface $paginator)
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }
    
        // Récupérer la page actuelle depuis la requête (par défaut page 1)
        $page = $request->query->getInt('page', 1);
        
        // Récupérer le critère de tri (date par défaut)
        $sortBy = $request->query->get('sortBy', 'createdAt'); // 'createdAt' ou 'prixTTC'
        $commande = $request->query->get('commande', 'DESC'); // 'ASC' ou 'DESC'
        $statutCommande = $request->query->get('statutCommande', null);
    
        // Ajouter la logique pour trier par statut
        if ($statutCommande) {
            $query = $commandeRepository->findBy(
                ['utilisateur' => $user], 
                ['status' => $statutCommande]  // Trier selon le statut
            );
        } else {
            $query = $commandeRepository->findBy(
                ['utilisateur' => $user], 
                [$sortBy => $commande]  // Sinon trier par date ou prix
            );
        }
    
        // Appliquer la pagination
        $commandes = $paginator->paginate(
            $query,             // La requête
            $page,              // La page actuelle
            10                  // Le nombre d'éléments par page
        );
    
        // Renvoyer les commandes à la vue
        return $this->render('pages/commande/historique.html.twig', [
            'commandes' => $commandes,
            'sortBy' => $sortBy,
            'commande' => $commande,
            'statutCommande' => $statutCommande
        ]);
    }

    #[Route('/commande/adresse/{type}', name: 'commande.adresse')]
public function adresseForm(
    Request $request, 
    EntityManagerInterface $em, 
    CommandeRepository $commandeRepository, 
    string $type
) {
    $user = $this->getUser();
    
    if (!$user) {
        return $this->redirectToRoute('app_login');
    }

    $adresse = new Adresse();
    $adresse->setUtilisateur($user);
    $adresse->setIsFacturation($type === 'facturation');

    $form = $this->createForm(AdresseType::class, $adresse);
    $form->handleRequest($request);

    // 🔹 Récupération de la dernière commande en attente de l'utilisateur
    $commande = $commandeRepository->findOneBy(
        ['utilisateur' => $user, 'status' => StatutCommande::EN_ATTENTE], 
        ['createdAt' => 'DESC']
    );

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($adresse);
        $em->flush();

        $this->addFlash('success', 'Adresse ajoutée avec succès!');

        // 🔹 Vérification et redirection vers le paiement si une commande existe
        if ($commande) {
            return $this->redirectToRoute('commande.paiement', ['id' => $commande->getId()]);
        }

        return $this->redirectToRoute('commande.ajout');
    }

    return $this->render('pages/commande/adresse.html.twig', [
        'form' => $form->createView(),
        'type' => $type,
        'commande' => $commande // 🔹 On passe la commande à la vue
    ]);
}

    
    
    #[Route('/commande/{id}/facture', name: 'commande.facture')]
    public function generateInvoice(int $id, CommandeRepository $commandeRepository, PDFService $pdfService): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }
        return $pdfService->generateInvoice($commande);
    }

    #[Route('/commande/{id}/bon-livraison', name: 'commande.bon_livraison')]
    public function generateDeliveryNote(int $id, CommandeRepository $commandeRepository, PDFService $pdfService): Response
    {
        $commande = $commandeRepository->find($id);
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }
        return $pdfService->generateDeliveryNote($commande);
    }


}
