<?php

namespace App\Controller;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Entity\PanierProduit;
use App\Repository\ProduitRepository;
use App\Repository\CommandeRepository;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PanierProduitRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProduitController extends AbstractController
{
        public function __construct(private ProduitRepository $produitRepository, private EntityManagerInterface $em)
    {
        $this->produitRepository = $produitRepository;
        $this->em = $em;
    }
    /**
     * Cette fonction affiche tout les produits
     *
     * @param ProduitRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    #[Route('/produit', name: 'produit', methods:['GET'])]
    public function index(PanierProduitRepository $panierProduitRepository, ProduitRepository $produitRepository, PaginatorInterface $paginator , Request $request, CommandeRepository $commande): Response
    {
        $produits = $paginator->paginate
        (
            $produitRepository->findAll(),
            $request->query->getInt('page', 1),
            12
        );
        
        $topProduits = $panierProduitRepository->getTopSellingProducts();
        // dd($topProduits);

        return $this->render('pages/produit/produit.html.twig', [
            'produits' => $produits,
            'topProduits' => $topProduits

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
    
    #[Route('/produit/{slug}', 'produit.detail')]
    public function detail(string $slug): Response
    {
        // Récupérer le produit à partir du slug
        $produit = $this->em
            ->getRepository(Produit::class)
            ->findOneBy(['slug' => $slug]);

        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
        // dd($produit);
        // Afficher la vue de détail du produit
        return $this->render('pages/produit/detail.html.twig', [
            'produit' => $produit,
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
