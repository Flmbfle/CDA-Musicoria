<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategorieController extends AbstractController
{
    /**
     * Cette fonction affiche toute les categories parents
     *
     * @param CategorieRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @return Response
     */
    #[Route('/categorie', name: 'categorie')]
    public function index(CategorieRepository $categorieRepository, PaginatorInterface $paginator, Request $request): Response
    {
        // Récupérer toutes les catégories parentes
        $query = $categorieRepository->createQueryBuilder('c')
            ->where('c.parent IS NULL')
            ->getQuery();

        // Pagination : 10 catégories par page
        $categoriesParentes = $paginator->paginate(
            $query,                // La requête
            $request->query->getInt('page', 1), // La page actuelle
            6                     // Le nombre d'éléments par page
        );

        return $this->render('pages/categorie/categorie.html.twig', [
            'categories' => $categoriesParentes,
        ]);
    }

        /**
     * Cette fonction affiche un formulaire de création de categorie
     *
     * @param EntityManagerInterface $manager
     * @param Request $request
     * @return Response
     */
    #[Route('/categorie/nouveau', name: 'categorie.nouveau', methods:['POST', 'GET'])]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        $categorie = new Categorie();  // Nouvelle entité sans ID
        $form = $this->createForm(CategorieType::class, $categorie);
        
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $categorie = $form->getData();

            $manager->persist($categorie);
            $manager->flush();
            
            $this->addFlash(
                'success',
                'Votre catégorie à été ajouté avec succès !'
            );

            return $this->redirectToRoute('categorie');
        }
    
        return $this->render('pages/categorie/nouveau.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Cette fonction affiche toute les sousCategories
     *
     * @param CategorieRepository $repository
     * @param PaginatorInterface $paginator
     * @param Request $request
     * @param string $slug
     * @return Response
     */
    #[Route('/categorie/{slug}', name: 'sousCategorie')]
    public function showChildren(CategorieRepository $categorieRepository, PaginatorInterface $paginator, Request $request, string $slug): Response
    {
        // Récupérer la catégorie parent en utilisant le slug
        $parent = $categorieRepository->findOneBy(['slug' => $slug]);

        if (!$parent) {
            throw $this->createNotFoundException('La catégorie demandée n\'existe pas.');
        }

        // Récupérer les sousCatégorie
        $query = $categorieRepository->createQueryBuilder('c')
            ->where('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->getQuery();

        // Pagination : 10 sous-catégories par page
        $sousCategorie = $paginator->paginate(
            $query,                // La requête
            $request->query->getInt('page', 1), // La page actuelle
            6                    // Le nombre d'éléments par page
        );

        return $this->render('pages/categorie/sousCategorie.html.twig', [
            'parent' => $parent,
            'sousCategorie' => $sousCategorie,
        ]);
    }

    /**
     * Cette fonction affiche un formulaire de modification de categorie
     * 
     * @param Categorie $categorie
     * @param Request $request
     * @param EntityManagerInterface $manager
     * @return Response
     * 
     */
    #[Route('/categorie/modifier/{id}', 'categorie.modifier', methods: ['GET','POST'])]
    public function edit(Categorie $categorie, Request $request, EntityManagerInterface $manager) :  Response
    {
        $form = $this->createForm(CategorieType::class, $categorie);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $categorie = $form->getData();

            $manager->persist($categorie);
            $manager->flush();

            $this->addFlash(
                'success',
                'Votre categorie à été modifié avec succès !'
            );

            return $this->redirectToRoute('categorie');
        }

        return $this->render('pages/categorie/modifier.html.twig', [
            'form' => $form->createView()
        ]);
    }


    /**
     * Cette fonction permet de supprimer une categorie
     * 
     * @param Categorie $categorie
     * @param EntityManagerInterface $manager
     * @return Response
     * 
     */
    #[Route('/categorie/supprimer/{id}', 'categorie.supprimer', methods: ['GET'])]
    public function delete(EntityManagerInterface $manager, Categorie $categorie) : Response
    {

        $manager->remove($categorie);
        $manager->flush();

        $this->addFlash(
            'success',
            'Votre categorie à été supprimé avec succès !'
        );

        return $this->redirectToRoute('categorie');
    }
}