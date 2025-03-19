<?php

namespace App\Controller;

use App\Repository\PanierProduitRepository;
use App\Repository\PanierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'accueil')]
    public function index(PanierProduitRepository $panierProduitRepository): Response
    {
        $topProduits = $panierProduitRepository->getTopSellingProducts();

        return $this->render('pages/main/index.html.twig', [
            'controller_name' => 'MainController',
            'topProduits' => $topProduits,
        ]);
    }
}
