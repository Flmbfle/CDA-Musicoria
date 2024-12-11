<?php

namespace App\Controller;

use Stripe\Stripe;
use Stripe\Invoice;
use Stripe\Webhook;
use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Enum\StatutCommande;
use Stripe\Checkout\Session;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
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
            'metadata' => [
                'commande_id' => $commande->getID(), // Ajoute l'ID de la commande
            ],
            'success_url' => $YOUR_DOMAIN . '/paiement/success',
            'cancel_url' => $YOUR_DOMAIN . '/paiement/cancel',
        ]);
        
        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);

        return $this->redirect($checkout_session->url, 303);
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

    public function retrieveInvoice($sessionId)
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    
        // Récupérer la session de paiement Stripe
        $session = Session::retrieve($sessionId);
    
        // Vérifier si une facture a été générée
        if ($session->invoice) {
            // Récupérer la facture
            $invoice = Invoice::retrieve($session->invoice);
        
            // Vous pouvez maintenant récupérer l'URL de la facture en PDF
            $invoicePdfUrl = $invoice->hosted_invoice_url; // URL de la facture PDF
        
            // Exemple: rediriger l'utilisateur vers la facture PDF
            return $this->redirect($invoicePdfUrl);
        }
    
        throw new \Exception('Aucune facture générée pour cette session.');
    }

    // Gestionnaire du webhook Stripe : vérifie l'authenticité des notifications Stripe, traite les événements 
    // de type "checkout.session.completed", et met à jour le statut des commandes dans la base de données.

    #[Route('/webhook', name: 'stripe_webhook')]
    public function handleStripeWebhook(Request $request, CommandeRepository $commandeRepository): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        // Récupérer la clé secrète du webhook
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];
    
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $event = null;
    
        try {
            // Vérification de la signature du webhook
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            // Payload invalide
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            // Signature invalide
            return new Response('Invalid signature', 400);
        }
    
        // Traiter les événements
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
        
            // Récupération des métadonnées
            $commandeId = $session->metadata->commande_id;
            $commande = $commandeRepository->find($commandeId);

            if ($commande) {
                $commande->setStatus(StatutCommande::VALIDEE);
                $this->em->persist($commande);
                $this->em->flush();
            }
        }
    
    return new Response('Webhook handled', 200);
    }
}