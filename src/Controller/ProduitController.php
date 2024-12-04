<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    /**
     * Cette fonction affiche un formulaire de création de produit
     *
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     */
    #[Route('/produit/nouveau', name: 'produit.nouveau', methods:['POST', 'GET'])]
    public function new(Request $request , EntityManagerInterface $manager) : Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $produit = $form->getData();

            $manager->persist($produit);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre produit à été ajouté avec succès !'
            );

            return $this->redirectToRoute('produit');
        }
        
        // dd($produit);
        return $this->render('pages/produit/nouveau.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Cette fonction affiche un formulaire de modification de produit
     * 
     * @param Produit $produit
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     * 
     */
    #[Route('/produit/modifier/{id}', 'produit.modifier', methods: ['GET','POST'])]
    public function edit(Produit $produit, Request $request, EntityManagerInterface $manager) :  Response
    {
        $form = $this->createForm(ProduitType::class, $produit);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $produit = $form->getData();

            $manager->persist($produit);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre produit à été modifié avec succès !'
            );

            return $this->redirectToRoute('produit');
        }

        return $this->render('pages/produit/modifier.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * Cette fonction permet de supprimer un produit
     * 
     * @param Produit $produit
     * @param EntityManagerInterface $manager
     * @return Response
     * 
     */
    #[Route('/produit/supprimer/{id}', 'produit.supprimer', methods: ['GET'])]
    public function delete(EntityManagerInterface $manager, Produit $produit) : Response
    {

        $manager->remove($produit);
        $manager->flush();

        $this->addFlash(
            'success',
            'Votre produit à été supprimé avec succès !'
        );

        return $this->redirectToRoute('produit');
    }
}
