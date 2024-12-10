<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use Stripe\Checkout\Session;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

class PaymentController extends AbstractController
{

    private EntityManagerInterface $em;
    private UrlGeneratorInterface $generator;

    public function __construct(EntityManagerInterface $em, UrlGeneratorInterface $generator)
    {
        $this->em = $em;
        $this->generator = $generator;
    }

    #[Route('commande/{id}/paiement', 'commande.paiement', requirements: ['id' => '\d+'])]
    #[ParamConverter('commande', Commande::class)]  
    public function paiement(CommandeRepository $commandeRepository, EntityManagerInterface $em, int $id): Response
    {
        $commande = $commandeRepository->find($id);

        if(!$commande)
        {
            throw $this->createAccessDeniedException('Commande non trouvé !');
        }

        $user = $this->getUser();
        if (!$user instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Utilisateur non valide.');
        }

        if($commande->getUtilisateur() !== $user) 
        {
            throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande !');
        }

        $stripeSecretKey = $_ENV['STRIPE_SECRET_KEY'];
        $userEmail = $user->getEmail();
        $YOUR_DOMAIN = 'http://0.0.0.0:8000';

        Stripe::setApiKey($stripeSecretKey);        

        $lineItems = [];

        foreach ($commande->getPanier()->getProduits() as $panierProduit) {
            $produit = $panierProduit->getProduit();
            $prixUnitaire = $panierProduit->getPrix();
            $quantite = $panierProduit->getQuantite();

            // Calculer le montant total pour chaque produit
            $totalProduitTTC = $prixUnitaire * 1.20;  // Appliquer la TVA de 20%
            $totalProduitTTC = $totalProduitTTC * $quantite;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur', // La devise
                    'product_data' => [
                        'name' => $produit->getLibelle(),
                        'description' => $produit->getDescription(),
                    ],
                    'unit_amount' => $totalProduitTTC * 100, // Le prix en centimes
                ],
                'quantity' => $quantite, // La quantité
            ];
        } 

        $checkout_session = Session::create([
            'billing_address_collection' => 'required',
            'custom_text' => [
                'submit' => [
                    'message' => 'En cliquant sur j\'accepte, vous renoncez à votre droit à un délai de rétractation de 14 jours et ne pourrez demander un remboursement.'
                ],
            ],
            // 'consent_collection' => [
            //     'terms_of_service' => 'required',
            // ],
            'customer_email' => $userEmail,
            'line_items' => $lineItems,
            'mode' => 'payment',
            'allow_promotion_codes' => true,
            'invoice_creation' => [
                'enabled' => true,
                'invoice_data' => [
                    'custom_fields' => [
                        [
                            'name' => 'SIRET',
                            'value' => 'MusicoriaDevSIRET',
                        ],
                        [
                            'name' => 'TVA',
                            'value' => '20 %',
                        ],
                    ],
                ],
            ],
            'success_url' => $YOUR_DOMAIN . '/paiement/success',
            'cancel_url' => $YOUR_DOMAIN . '/paiement/cancel',
        ]);
        
        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);

        return $this->render('pages/paiement/index.html.twig');
    }

    #[Route('paiement/success', 'paiement.success')]
    public function success(): Response
    {
        return $this->render('pages/paiement/success.html.twig');
    }    
    
    #[Route('paiement/cancel', 'paiement.cancel')]
    public function cancel(): Response
    {
        $this->addFlash('cancel', 'Votre achat à été annulé');

        return $this->redirectToRoute('accueil');
    }
}