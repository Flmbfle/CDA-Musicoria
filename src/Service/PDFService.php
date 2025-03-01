<?php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;

class PDFService
{
    private $dompdf;

    public function __construct()
    {
        // Configuration de Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $this->dompdf = new Dompdf($options);
    }

    public function generateInvoice($commande): Response
    {
        // Récupère les données de la commande et crée le contenu HTML pour la facture
        $html = $this->generateInvoiceHtml($commande);

        // Charge le HTML dans Dompdf
        $this->dompdf->loadHtml($html);

        // Rend la page PDF
        $this->dompdf->render();

        // Créer une réponse pour télécharger le PDF
        $pdfContent = $this->dompdf->output();
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="facture-' . $commande->getId() . '.pdf"',
        ]);
    }

    private function generateInvoiceHtml($commande)
    {
        // Génère le HTML pour la facture avec du style
        $html = '<html>
                    <head>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                margin: 0;
                                padding: 0;
                            }
                            .container {
                                width: 90%;
                                margin: 10px auto;
                                background-color: #ffffff;
                                padding: 25px;
                                border-radius: 8px;
                                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                            }
                            h1, h2, h3 {
                                color: #333;
                                margin-bottom: 10px;
                            }
                            h1 {
                                font-size: 26px;
                            }
                            h2 {
                                font-size: 22px;
                            }
                            p {
                                font-size: 16px;
                                color: #555;
                                margin: 5px 0;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                                margin-top: 20px;
                            }
                            table th, table td {
                                padding: 10px;
                                text-align: left;
                                border-bottom: 1px solid #ddd;
                            }
                            table th {
                                background-color: #f2f2f2;
                            }
                            .total {
                                font-weight: bold;
                                font-size: 16px;
                                background-color: #f9f9f9;
                            }
                            .footer {
                                margin-top: 30px;
                                text-align: center;
                                font-size: 14px;
                                color: #888;
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h1>Facture</h1>
                            <p><strong>Date de commande:</strong> ' . $commande->getCreatedAt()->format('d/m/Y') . '</p>
                            <p><strong>Numéro de commande:</strong> ' . $commande->getId() . '</p>
                            <p><strong>Client:</strong> ' . $commande->getUtilisateur()->getFullName() . '</p>
                            <p><strong>Email:</strong> ' . $commande->getUtilisateur()->getEmail() . '</p>';
    
        // Récupérer l'utilisateur
        $utilisateur = $commande->getUtilisateur();
    
        // Vérifier les adresses et utiliser celle avec getIsFacturation() == true
        $adresseFacturation = null;
        foreach ($utilisateur->getAdresse() as $adresse) {
            if ($adresse->getIsFacturation()) {
                $adresseFacturation = $adresse;
                break;
            }
        }
    
        // Afficher l'adresse de facturation si elle existe
        if ($adresseFacturation) {
            $adresseComplete = $adresseFacturation->getLigne1();
            if ($adresseFacturation->getLigne2()) {
                $adresseComplete .= ' ' . $adresseFacturation->getLigne2();
            }
            $adresseComplete .= ' ' . $adresseFacturation->getCodePostal() . ' ' . $adresseFacturation->getVille();
            $adresseComplete .= ' ' . $adresseFacturation->getPays();
            
            $html .= '<h3>Adresse de facturation</h3>';
            $html .= '<p>' . $adresseComplete . '</p>';
        } else {
            $html .= '<h3>Adresse de facturation</h3>';
            $html .= '<p>Aucune adresse de facturation définie.</p>';
        }
    
        // Adresse de livraison
        $adresseLivraison = $utilisateur->getAdresse(); // Supposons que getAdresse() renvoie l'adresse de livraison
        
        if ($adresseLivraison) {
            $adresseComplete = $adresseLivraison->getLigne1();
            if ($adresseLivraison->getLigne2()) {
                $adresseComplete .= ' ' . $adresseLivraison->getLigne2();
            }
            $adresseComplete .= ' ' . $adresseLivraison->getCodePostal() . ' ' . $adresseLivraison->getVille();
            $adresseComplete .= ' ' . $adresseLivraison->getPays();
            
            $html .= '<h3>Adresse de livraison</h3>';
            $html .= '<p>' . $adresseComplete . '</p>';
        } else {
            $html .= '<p>Adresse de livraison: Non renseignée</p>';
        }
    
        // Tableau des produits
        $html .= '<h3>Produits</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Nom</th><th>Quantité</th><th>Prix Unitaire</th><th>Total HT</th><th>TVA</th><th>Total TTC</th></tr>';
    
        $totalHT = 0;
        $totalTVA = 0;
        foreach ($commande->getPanier()->getProduits() as $panierProduit) {
            $produit = $panierProduit->getProduit();
            $quantite = $panierProduit->getQuantite();
            $prixUnitaire = $panierProduit->getPrix();
            $totalProduitHT = $prixUnitaire * $quantite;
            $tvaProduit = $totalProduitHT * 0.20; // Calcul de la TVA 20%
            $totalProduitTTC = $totalProduitHT + $tvaProduit;
    
            $totalHT += $totalProduitHT;
            $totalTVA += $tvaProduit;
    
            $html .= '<tr>';
            $html .= '<td>' . $produit->getLibelle() . '</td>';
            $html .= '<td>' . $quantite . '</td>';
            $html .= '<td>' . number_format($prixUnitaire, 2, ',', ' ') . ' €</td>';
            $html .= '<td>' . number_format($totalProduitHT, 2, ',', ' ') . ' €</td>';
            $html .= '<td>' . number_format($tvaProduit, 2, ',', ' ') . ' €</td>';
            $html .= '<td>' . number_format($totalProduitTTC, 2, ',', ' ') . ' €</td>';
            $html .= '</tr>';
        }
    
        // Calcul de la TVA et du Total TTC
        $totalTTC = $totalHT + $totalTVA;
    
        // Lignes vides pour les totaux
        $html .= '<tr class="total"><td colspan="3"></td><td>' . number_format($totalHT, 2, ',', ' ') . ' €</td>
        <td>' . number_format($totalTVA, 2, ',', ' ') . ' €</td><td>' . number_format($totalTTC, 2, ',', ' ') . ' €</td></tr>';
        $html .= '</table>';
    
        // Footer
        $html .= '<div class="footer">';
        $html .= '<p>Merci de votre commande.</p>';
        $html .= '</div>';
        
        $html .= '</div></body></html>';
    
        return $html;
    }
    

    public function generateDeliveryNote($commande): Response
    {
        // Récupère les données de la commande et crée le contenu HTML pour le bon de livraison
        $html = $this->generateDeliveryNoteHtml($commande);

        // Charge le HTML dans Dompdf
        $this->dompdf->loadHtml($html);

        // Rend la page PDF
        $this->dompdf->render();

        // Créer une réponse pour télécharger le PDF
        $pdfContent = $this->dompdf->output();
        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bon_de_livraison-' . $commande->getId() . '.pdf"',
        ]);
    }

    private function generateDeliveryNoteHtml($commande)
    {
        // Génère le HTML pour le bon de livraison
        $html = '<h1>Bon de Livraison</h1>';
        $html .= '<h2>Commande #' . $commande->getId() . '</h2>';
        $html .= '<p>Destinataire: ' . $commande->getUtilisateur()->getFullName() . '</p>';
        $html .= '<p>Adresse de livraison: ' . $commande->getAdresseLivraison() . '</p>';
        $html .= '<h3>Produits Livrés</h3>';
        $html .= '<table>';
        $html .= '<tr><th>Nom</th><th>Quantité</th></tr>';

        foreach ($commande->getPanier()->getProduits() as $panierProduit) {
            $produit = $panierProduit->getProduit();
            $quantite = $panierProduit->getQuantite();

            $html .= '<tr>';
            $html .= '<td>' . $produit->getLibelle() . '</td>';
            $html .= '<td>' . $quantite . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';
        return $html;
    }
}
