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

            // Appliquer la TVA une seule fois
            $prixUnitaireTTC = $prixUnitaire * 1.20; // Appliquer la TVA de 20%
            $totalProduitTTC = $prixUnitaireTTC * $quantite; // Appliquer la quantité

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur', // La devise
                    'product_data' => [
                        'name' => $produit->getLibelle(),
                        'description' => $produit->getDescription(),
                    ],
                    'unit_amount' => $prixUnitaireTTC * 100, // Le prix en centimes
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
            'success_url' => $YOUR_DOMAIN . '/paiement/success?commande_id=' . $commande->getId(),
            'cancel_url' => $YOUR_DOMAIN . '/paiement/cancel',
        ]);
        
        header("HTTP/1.1 303 See Other");
        header("Location: " . $checkout_session->url);

        return $this->redirect($checkout_session->url, 303);
    }

    #[Route('/paiement/success', name: 'paiement.success')]
    public function success(Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $em): Response
    {
        // Récupérer l'ID de la commande depuis les paramètres de l'URL
        $commandeId = $request->query->get('commande_id');
        $commande = $commandeRepository->find($commandeId);
    
        if (!$commande) {
            throw $this->createNotFoundException('Commande non trouvée');
        }
    
        // Vérifier si le statut de la commande est encore "En attente"
        if ($commande->getStatus() === StatutCommande::EN_ATTENTE) {
            // Mettre à jour le statut de la commande à "Validée"
            $commande->setStatus(StatutCommande::VALIDEE);
            $em->persist($commande);
            $em->flush();
        }
    
        // Afficher la vue de succès pour l'utilisateur
        return $this->render('pages/paiement/success.html.twig', [
            'commande' => $commande
        ]);
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
    public function handleStripeWebhook(
        Request $request, 
        CommandeRepository $commandeRepository, 
        EntityManagerInterface $em
    ): Response {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        
        // Récupérer la clé secrète du webhook
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];
    
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
    
        try {
            // Vérification de la signature Stripe
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        }
    
        // Traiter l'événement de paiement réussi
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
    
            // Vérification de la présence des métadonnées
            if (!isset($session->metadata->commande_id)) {
                return new Response('Commande ID manquant', 400);
            }
    
            $commandeId = $session->metadata->commande_id;
            $commande = $commandeRepository->find($commandeId);
    
            if ($commande) {
                // Récupérer l'adresse de facturation depuis Stripe
                $billingAddress = $session->customer_details->address;
    
                if ($billingAddress) {
                    // Vérification et construction de l'adresse proprement
                    $adresseFacturation = trim(($billingAddress->line1 ?? '') . ' ' . ($billingAddress->line2 ?? ''))
                                          . ', ' . $billingAddress->city
                                          . ', ' . $billingAddress->postal_code
                                          . ', ' . $billingAddress->country;
    
                    // Mettre à jour l'adresse de facturation
                    $commande->setAdresseFacturation($adresseFacturation);
                }
    
                // Mettre à jour le statut de la commande à "Validée"
                $commande->setStatus(StatutCommande::VALIDEE);
    
                // Sauvegarder en base de données
                $em->persist($commande);
                $em->flush();
            }
        }
    
        return new Response('Webhook handled', 200);
    }
}