<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ProduitController extends AbstractController
{
    /**
     * Cette fonction affiche tout les produits
     *
     * @param ProduitRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    #[Route('/produit', name: 'produit', methods:['GET'])]
    public function index(ProduitRepository $repository, PaginatorInterface $paginator , Request $request): Response
    {
        $produits = $paginator->paginate
        (
            $repository->findAll(),
            $request->query->getInt('page', 1),
            12
        );

        return $this->render('pages/produit/produit.html.twig', [
            'produits' => $produits,
        ]);
    }

    #[Route('/produit/nouveau', name: 'nouveau.produit', methods:['POST', 'GET'])]
    public function new() : Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        return $this->render('pages/produit/nouveau.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
